<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Events\SubscriptionEvent;
use Railroad\Ecommerce\Repositories\AccessCodeRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Requests\AccessCodeClaimRequest;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Permissions\Services\PermissionService;
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
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * @var mixed UserProviderInterface
     */
    private $userProvider;

    /**
     * AccessCodeController constructor.
     *
     * @param AccessCodeRepository $accessCodeRepository
     * @param PermissionService $permissionService
     * @param ProductRepository $productRepository
     * @param SubscriptionRepository $subscriptionRepository
     */
    public function __construct(
        AccessCodeRepository $accessCodeRepository,
        CurrencyService $currencyService,
        PermissionService $permissionService,
        ProductRepository $productRepository,
        SubscriptionRepository $subscriptionRepository
    ) {
        parent::__construct();

        $this->accessCodeRepository = $accessCodeRepository;
        $this->currencyService = $currencyService;
        $this->permissionService = $permissionService;
        $this->productRepository = $productRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->userProvider = app()->make('UserProviderInterface');
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

        // add new users
        if ($request->has('email')) {
            $user = $this->userProvider->create(
                $request->get('email'),
                $request->get('password')
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

        // filter products of subscription type
        $subscriptionProducts = $accessCodeProducts
            ->filter(function ($product, $key) {
                return $product['type'] == ConfigService::$typeSubscription;
            });

        // get existing subscriptions
        $existingSubscriptions = $this->subscriptionRepository
            ->query()
            ->where('user_id', $user['id'])
            ->whereIn('product_id', $subscriptionProducts->pluck('id')->all())
            ->get();

        $processedProducts = [];

        // extend existing subscriptions
        foreach ($existingSubscriptions as $subscription) {

            $subscriptionCurrentEndDate = Carbon::parse(
                    $subscription['paid_until']
                );

            // if subscription is expired, the extension is calculated from current date
            $extensionStartDate = $subscriptionCurrentEndDate->isPast() ?
                Carbon::now() : $subscriptionCurrentEndDate;

            $product = $subscription['product'];
            $intervalCount = $product['subscription_interval_count'];

            switch ($product['subscription_interval_type']) {
                case ConfigService::$intervalTypeMonthly:
                    $endDate = $extensionStartDate->addMonths($intervalCount);
                break;

                case ConfigService::$intervalTypeYearly:
                    $endDate = $extensionStartDate->addYears($intervalCount);
                break;

                case ConfigService::$intervalTypeDaily:
                    $endDate = $extensionStartDate->addDays($intervalCount);
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

            event(new SubscriptionEvent($subscription['id'], 'extended')); // TO-DO: confirm

            $processedProducts[$product['id']] = true;
        }

        $currency = $this->currencyService->get();

        // add new subscriptions
        foreach ($subscriptionProducts as $product) {

            if (isset($processedProducts[$product['id']])) {
                continue;
            }

            $intervalCount = $product['subscription_interval_count'];

            switch ($product['subscription_interval_type']) {
                case ConfigService::$intervalTypeMonthly:
                    $endDate = Carbon::now()->addMonths($intervalCount);
                break;

                case ConfigService::$intervalTypeYearly:
                    $endDate = Carbon::now()->addYears($intervalCount);
                break;

                case ConfigService::$intervalTypeDaily:
                    $endDate = Carbon::now()->addDays($intervalCount);
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

            $endDate = $endDate->startOfDay()->toDateTimeString();

            $subscription = $this->subscriptionRepository->create([
                'brand' => $product['brand'],
                'type' => ConfigService::$typeSubscription,
                'user_id' => $user['id'],
                'order_id' => null,
                'product_id' => $product['id'],
                'is_active' => true,
                'start_date' => Carbon::now()->toDateTimeString(),
                'paid_until' => $endDate,
                'total_price_per_payment' => 0, // TO-DO: confirm
                'tax_per_payment' =>  null, // TO-DO: confirm
                'shipping_per_payment' => 0,
                'currency' => $currency,
                'interval_type' => $product['subscription_interval_type'],
                'interval_count' => $intervalCount,
                'total_cycles_paid' => 1,
                'total_cycles_due' => null,
                'payment_method_id' => null,
                'created_on' => Carbon::now()->toDateTimeString(),
            ]);
        }

        return reply()->form();
    }

    /**
     * Release an access code
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function release(Request $request)
    {
        throw new \BadMethodCallException('Not implemented');
    }
}
