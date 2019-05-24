<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Datetime;
use Railroad\Ecommerce\Entities\AccessCode;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\SubscriptionAccessCode;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Entities\UserProduct;
use Railroad\Ecommerce\Exceptions\UnprocessableEntityException;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\AccessCodeRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Throwable;

class AccessCodeService
{
    /**
     * @var CurrencyService
     */
    private $currencyService;

    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * @var UserProductService
     */
    private $userProductService;

    /**
     * @var AccessCodeRepository
     */
    private $accessCodeRepository;

    /**
     * AccessCodeService constructor.
     *
     * @param CurrencyService $currencyService
     * @param EcommerceEntityManager $entityManager
     * @param ProductRepository $productRepository
     * @param SubscriptionRepository $subscriptionRepository
     * @param UserProductService $userProductService
     * @param AccessCodeRepository $accessCodeRepository
     */
    public function __construct(
        CurrencyService $currencyService,
        EcommerceEntityManager $entityManager,
        ProductRepository $productRepository,
        SubscriptionRepository $subscriptionRepository,
        UserProductService $userProductService,
        AccessCodeRepository $accessCodeRepository
    )
    {
        $this->currencyService = $currencyService;
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->userProductService = $userProductService;
        $this->accessCodeRepository = $accessCodeRepository;
    }

    /**
     * Sets up the $accessCode as claimed by $user
     * extends $accessCode associated subscriptions
     * adds user products
     *
     * @param string $rawAccessCode
     * @param User $user
     *
     * @return AccessCode
     *
     * @throws Throwable
     */
    public function claim(string $rawAccessCode, User $user): AccessCode
    {
        $accessCode = $this->accessCodeRepository->findOneBy(['code' => $rawAccessCode]);

        $accessCodeProducts = $this->productRepository->byAccessCode($accessCode);

        $subscriptions = $this->subscriptionRepository->getProductsSubscriptions($accessCodeProducts);

        $processedProductsIds = [];

        // extend subscriptions

        foreach ($subscriptions as $subscription) {
            /**
             * @var $subscription Subscription
             */

            /**
             * @var $paidUntil Datetime
             */
            $paidUntil = $subscription->getPaidUntil();

            /**
             * @var $subscriptionEndDate Carbon
             */
            $subscriptionEndDate = Carbon::instance($paidUntil);

            // if subscription is expired, the access code will create a UserProduct
            if ($subscriptionEndDate->isPast()) {
                continue;
            }

            /**
             * @var $product Product
             */
            $product = $subscription->getProduct();
            $intervalCount = $product->getSubscriptionIntervalCount();

            switch ($product->getSubscriptionIntervalType()) {
                case config('ecommerce.interval_type_monthly'):
                    $endDate = $subscriptionEndDate->addMonths($intervalCount);
                    break;

                case config('ecommerce.interval_type_yearly'):
                    $endDate = $subscriptionEndDate->addYears($intervalCount);
                    break;

                case config('ecommerce.interval_type_daily'):
                    $endDate = $subscriptionEndDate->addDays($intervalCount);
                    break;

                default:
                    $format = 'Unknown subscription interval type for product id %s: %s';
                    $message = sprintf($format, $product->getId(), $product->getSubscriptionIntervalType());

                    throw new UnprocessableEntityException($message);
                    break;
            }

            $subscription->setIsActive(true);
            $subscription->setCanceledOn(null);
            $subscription->setTotalCyclesPaid($subscription->getTotalCyclesPaid() + 1);
            $subscription->setPaidUntil($endDate->startOfDay());
            $subscription->setUpdatedAt(Carbon::now());

            $subscriptionAccessCode = new SubscriptionAccessCode();

            $subscriptionAccessCode->setSubscription($subscription);
            $subscriptionAccessCode->setAccessCode($accessCode);
            $subscriptionAccessCode->setCreatedAt(Carbon::now());
            $subscriptionAccessCode->setUpdatedAt(Carbon::now()); // explicit date handling required for automated tests

            $this->entityManager->persist($subscriptionAccessCode);

            $processedProductsIds[$product->getId()] = true;
        }

        // add user products

        /**
         * @var $product Product
         */
        foreach ($accessCodeProducts as $product) {

            if (isset($processedProductsIds[$product->getId()])) {
                continue;
            }

            $intervalCount = $product->getSubscriptionIntervalCount();
            $expirationDate = null;

            switch ($product->getSubscriptionIntervalType()) {
                case config('ecommerce.interval_type_monthly'):
                    $expirationDate =
                        Carbon::now()
                            ->addMonths($intervalCount)
                            ->startOfDay();
                    break;

                case config('ecommerce.interval_type_yearly'):
                    $expirationDate =
                        Carbon::now()
                            ->addYears($intervalCount)
                            ->startOfDay();
                    break;

                case config('ecommerce.interval_type_daily'):
                    $expirationDate =
                        Carbon::now()
                            ->addDays($intervalCount)
                            ->startOfDay();
                    break;
            }

            $userProduct = new UserProduct();

            $userProduct->setUser($user);
            $userProduct->setProduct($product);
            $userProduct->setQuantity(1);
            $userProduct->setExpirationDate($expirationDate);

            $this->entityManager->persist($userProduct);
        }

        $accessCode->setIsClaimed(true);
        $accessCode->setClaimer($user);
        $accessCode->setClaimedOn(Carbon::now());
        $accessCode->setUpdatedAt(Carbon::now());

        $this->entityManager->persist($accessCode);
        $this->entityManager->flush();

        return $accessCode;
    }
}