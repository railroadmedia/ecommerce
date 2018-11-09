<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repositories\AccessCodeRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\SubscriptionAccessCodeRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Requests\AccessCodeClaimRequest;
use Railroad\Ecommerce\Requests\AccessCodeReleaseRequest;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\UserProductService;
use Railroad\Permissions\Services\PermissionService;
use Railroad\Usora\Repositories\UserRepository;
use Throwable;

class AccessCodeController extends BaseController
{
    /**
     * @var AccessCodeRepository
     */
    private $accessCodeRepository;

    /**
     * @var CurrencyService
     */
    private $currencyService;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var SubscriptionAccessCodeRepository
     */
    private $subscriptionAccessCodeRepository;

    /**
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * @var mixed UserProductService
     */
    private $userProductService;

    /**
     * @var mixed UserProviderInterface
     */
    private $userProvider;

    /**
     * AccessCodeController constructor.
     *
     * @param AccessCodeRepository $accessCodeRepository
     * @param CurrencyService $currencyService
     * @param PermissionService $permissionService
     * @param ProductRepository $productRepository
     * @param SubscriptionAccessCodeRepository $subscriptionAccessCodeRepository
     * @param SubscriptionRepository $subscriptionRepository
     * @param UserProductService $userProductService
     * @param UserRepository $userRepository
     */
    public function __construct(
        AccessCodeRepository $accessCodeRepository,
        CurrencyService $currencyService,
        PermissionService $permissionService,
        ProductRepository $productRepository,
        SubscriptionAccessCodeRepository $subscriptionAccessCodeRepository,
        SubscriptionRepository $subscriptionRepository,
        UserProductService $userProductService,
        UserRepository $userRepository
    ) {
        parent::__construct();

        $this->accessCodeRepository = $accessCodeRepository;
        $this->currencyService = $currencyService;
        $this->permissionService = $permissionService;
        $this->productRepository = $productRepository;
        $this->subscriptionAccessCodeRepository = $subscriptionAccessCodeRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->userProductService = $userProductService;
        $this->userProvider = app()->make('UserProviderInterface');
        $this->userRepository = $userRepository;
    }

    /**
     * Claim an access code
     *
     * @param AccessCodeClaimRequest $request
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function claim(AccessCodeClaimRequest $request)
    {
        $user = auth()->user() ?? null;

        if ($request->has('email')) {
            // add new user
            $user = $this->userProvider->create(
                $request->get('email'),
                $request->get('password')
            );

            auth()->loginUsingId($user['id'], true);

        } else if ($request->get('claim_for_user_email')) {
            // admin claims code for users
            $this->permissionService->canOrThrow(
                auth()->id(),
                'claim.access_codes'
            );

            $user = $this->userRepository
                ->query()
                ->where('email', '=', $request->get('claim_for_user_email'))
                ->first();

            throw_if(
                is_null($user),
                new NotFoundException(
                    'Claim failed, user not found with email: '
                    . $request->get('claim_for_user_email')
                )
            );
        }

        // get the access code data
        $accessCode = $this->accessCodeRepository
            ->query()
            ->where('code', '=', $request->get('access_code'))
            ->first();

        // get the products collection associated with the access code
        $accessCodeProducts = $this->productRepository
            ->query()
            ->whereIn('id', $accessCode['product_ids'])
            ->get();

        // get subscriptions
        $subscriptions = $this->subscriptionRepository
            ->query()
            ->where('user_id', $user['id'])
            ->whereIn('product_id', $accessCodeProducts->pluck('id')->all())
            ->get();

        $processedProducts = [];

        // extend subscriptions
        foreach ($subscriptions as $subscription) {

            $subscriptionEndDate = Carbon::parse(
                    $subscription['paid_until']
                );

            // if subscription is expired, the access code will create a user_product
            if ($subscriptionEndDate->isPast()) {
                continue;
            }

            $product = $subscription['product'];
            $intervalCount = $product['subscription_interval_count'];

            switch ($product['subscription_interval_type']) {
                case ConfigService::$intervalTypeMonthly:
                    $endDate = $subscriptionEndDate->addMonths($intervalCount);
                break;

                case ConfigService::$intervalTypeYearly:
                    $endDate = $subscriptionEndDate->addYears($intervalCount);
                break;

                case ConfigService::$intervalTypeDaily:
                    $endDate = $subscriptionEndDate->addDays($intervalCount);
                break;

                default:
                    $format = 'Unknown subscription interval type for product id %s: %s';
                    $message = sprintf(
                        $format,
                        $product['id'],
                        $product['subscription_interval_type']
                    );

                    throw new UnprocessableEntityException($message);
                break;
            }

            $this->subscriptionRepository->update(
                $subscription['id'],
                [
                    'is_active' => true,
                    'canceled_on' => null,
                    'total_cycles_paid' => $subscription['total_cycles_paid'] + 1,
                    'paid_until' => $endDate->startOfDay()->toDateTimeString(),
                    'updated_on' => Carbon::now()->toDateTimeString(),
                ]
            );

            $this->subscriptionAccessCodeRepository->create([
                'subscription_id' => $subscription['id'],
                'access_code_id' => $accessCode['id'],
                'created_on' => Carbon::now()->toDateTimeString()
            ]);

            $processedProducts[$product['id']] = true;
        }

        $currency = $this->currencyService->get();

        // add user products
        foreach ($accessCodeProducts as $product) {

            if (isset($processedProducts[$product['id']])) {
                continue;
            }

            $intervalCount = $product['subscription_interval_count'] ?? null;
            $expirationDate = null;

            switch ($product['subscription_interval_type']) {
                case ConfigService::$intervalTypeMonthly:
                    $expirationDate = Carbon::now()
                        ->addMonths($intervalCount)
                        ->startOfDay()
                        ->toDateTimeString();
                break;

                case ConfigService::$intervalTypeYearly:
                    $expirationDate = Carbon::now()
                        ->addYears($intervalCount)
                        ->startOfDay()
                        ->toDateTimeString();
                break;

                case ConfigService::$intervalTypeDaily:
                    $expirationDate = Carbon::now()
                        ->addDays($intervalCount)
                        ->startOfDay()
                        ->toDateTimeString();
                break;
            }

            $this->userProductService->saveUserProduct(
                $user['id'],
                $product['id'],
                1,
                $expirationDate
            );
        }

        $this->accessCodeRepository->update(
            $accessCode['id'],
            [
                'is_claimed' => true,
                'claimer_id' => $user['id'],
                'claimed_on' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString()
            ]
        );

        return reply()->form(
            [true],
            null,
            [],
            ['access_code' => true]
        );
    }

    /**
     * Release an access code
     *
     * @param AccessCodeReleaseRequest $request
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function release(AccessCodeReleaseRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'release.access_codes');

        $stuff = $this->accessCodeRepository->update(
            $request->get('access_code_id'),
            [
                'is_claimed' => false,
                'claimer_id' => null,
                'claimed_on' => null
            ]
        );

        return reply()->form();
    }
}
