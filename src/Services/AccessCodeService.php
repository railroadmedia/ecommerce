<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\UserProductService;
use Railroad\Ecommerce\Entities\AccessCode;
use Railroad\Usora\Entities\User;

class AccessCodeService
{
    /**
     * @var CurrencyService
     */
    private $currencyService;

    /**
     * @var mixed UserProductService
     */
    private $userProductService;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * AccessCodeService constructor.
     *
     * @param CurrencyService $currencyService
     * @param EntityManager $entityManager
     * @param UserProductService $userProductService
     */
    public function __construct(
        CurrencyService $currencyService,
        EntityManager $entityManager,
        UserProductService $userProductService
    ) {
        $this->currencyService = $currencyService;
        $this->entityManager = $entityManager;
        $this->userProductService = $userProductService;
    }

    public function claim(AccessCode $accessCode, User $user)
    {
        // TO-DO: replicate the logic below, using entites and doctrine entity manager
        return $accessCode;
    }

    public function deprecated_claim($accessCode, $user)
    {
        /*
        // get the access code data
        $accessCode = $this->accessCodeRepository
            ->query()
            ->where('code', '=', $accessCode)
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

        $accessCode = $this->accessCodeRepository->update(
            $accessCode['id'],
            [
                'is_claimed' => true,
                'claimer_id' => $user['id'],
                'claimed_on' => Carbon::now()->toDateTimeString(),
                'updated_on' => Carbon::now()->toDateTimeString()
            ]
        );

        return $accessCode;

        */
    }
}