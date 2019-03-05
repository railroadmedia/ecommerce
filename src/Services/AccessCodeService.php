<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Railroad\Ecommerce\Contracts\UserInterface;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\UserProductService;
use Railroad\Ecommerce\Entities\AccessCode;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\SubscriptionAccessCode;
use Railroad\Ecommerce\Entities\UserProduct;

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

    public function claim(AccessCode $accessCode, UserInterface $user)
    {
        $productRepository = $this->entityManager
            ->getRepository(Product::class);

        $accessCodeProducts = $productRepository
            ->getAccessCodeProducts($accessCode);

        $subscriptionRepository = $this->entityManager
            ->getRepository(Subscription::class);

        $subscriptions = $subscriptionRepository
            ->getProductsSubscriptions($accessCodeProducts);

        $processedProductsIds = [];

        // extend subscriptions

        /**
         * @var $subscription \Railroad\Ecommerce\Entities\Subscription
         */
        foreach ($subscriptions as $subscription) {

            /**
             * @var $subscriptionEndDate \Carbon\Carbon
             */
            $subscriptionEndDate = Carbon::instance(
                    $subscription->getPaidUntil()
                );

            // if subscription is expired, the access code will create a UserProduct
            if ($subscriptionEndDate->isPast()) {
                continue;
            }

            /**
             * @var $product \Railroad\Ecommerce\Entities\Product
             */
            $product = $subscription->getProduct();
            $intervalCount = $product->getSubscriptionIntervalCount();

            switch ($product->getSubscriptionIntervalType()) {
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
                        $product->getId(),
                        $product->getSubscriptionIntervalType()
                    );

                    throw new UnprocessableEntityException($message);
                break;
            }

            $subscription
                ->setIsActive(true)
                ->setCanceledOn(null)
                ->setTotalCyclesPaid($subscription->getTotalCyclesPaid() + 1)
                ->setPaidUntil($endDate->startOfDay())
                ->setUpdatedAt(Carbon::now());

            $subscriptionAccessCode = new SubscriptionAccessCode();

            $subscriptionAccessCode
                ->setSubscription($subscription)
                ->setAccessCode($accessCode)
                ->setCreatedAt(Carbon::now())
                ->setUpdatedAt(Carbon::now()); // explicit date handling required for automated tests

            $this->entityManager->persist($subscriptionAccessCode);

            $processedProducts[$product->getId()] = true;
        }

        $currency = $this->currencyService->get();

        // add user products

        /**
         * @var $product \Railroad\Ecommerce\Entities\Product
         */
        foreach ($accessCodeProducts as $product) {

            if (isset($processedProducts[$product->getId()])) {
                continue;
            }

            $intervalCount = $product->getSubscriptionIntervalCount();
            $expirationDate = null;

            switch ($product->getSubscriptionIntervalType()) {
                case ConfigService::$intervalTypeMonthly:
                    $expirationDate = Carbon::now()
                                        ->addMonths($intervalCount)
                                        ->startOfDay();
                break;

                case ConfigService::$intervalTypeYearly:
                    $expirationDate = Carbon::now()
                                        ->addYears($intervalCount)
                                        ->startOfDay();
                break;

                case ConfigService::$intervalTypeDaily:
                    $expirationDate = Carbon::now()
                                        ->addDays($intervalCount)
                                        ->startOfDay();
                break;
            }

            $userProduct = new UserProduct();

            $userProduct
                ->setUser($user)
                ->setProduct($product)
                ->setQuantity(1)
                ->setExpirationDate($expirationDate);

            $this->entityManager->persist($userProduct);
        }

        $accessCode
            ->setIsClaimed(true)
            ->setClaimer($user)
            ->setClaimedOn(Carbon::now())
            ->setUpdatedAt(Carbon::now());

        $this->entityManager->persist($accessCode);
        $this->entityManager->flush();

        return $accessCode;
    }
}