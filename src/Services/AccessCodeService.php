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
use Railroad\Ecommerce\Events\AccessCodeClaimed;
use Railroad\Ecommerce\Events\UserProducts\UserProductCreated;
use Railroad\Ecommerce\Exceptions\UnprocessableEntityException;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\AccessCodeRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Throwable;

/**
 * Class AccessCodeService
 * @package Railroad\Ecommerce\Services
 */
class AccessCodeService
{
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
     * @param EcommerceEntityManager $entityManager
     * @param ProductRepository $productRepository
     * @param SubscriptionRepository $subscriptionRepository
     * @param UserProductService $userProductService
     * @param AccessCodeRepository $accessCodeRepository
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        ProductRepository $productRepository,
        SubscriptionRepository $subscriptionRepository,
        UserProductService $userProductService,
        AccessCodeRepository $accessCodeRepository
    )
    {
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
     * @param null $context
     * @return AccessCode
     *
     * @throws Throwable
     */
    public function claim(string $rawAccessCode, User $user, $context = null): AccessCode
    {
        $accessCode = $this->accessCodeRepository->findOneBy(['code' => $rawAccessCode]);

        $accessCodeProducts = $this->productRepository->byAccessCode($accessCode);

        // get user active subscriptions, should be just one
        $subscriptions = $this->subscriptionRepository->getUserActiveSubscription($user);

        $processedProductsIds = [];

        // extend subscriptions
        /** @var $subscription Subscription */
        foreach ($subscriptions as $subscription) {

            if($subscription->getType() === 'payment plan') continue;

            /** @var $paidUntil Datetime */
            $paidUntil = $subscription->getPaidUntil();

            /** @var $subscriptionEndDate Carbon */
            $subscriptionEndDate = Carbon::instance($paidUntil);

            // if subscription is expired, the access code will create a UserProduct
            if ($subscriptionEndDate->isPast()) {
                continue;
            }

            /** @var $product Product */
            foreach ($accessCodeProducts as $product) {

                if ($product->getType() != Product::TYPE_DIGITAL_SUBSCRIPTION) {
                    // for subscription extending, only subscription products are processed in this block
                    continue;
                }

                $intervalCount = $product->getSubscriptionIntervalCount();

                switch ($product->getSubscriptionIntervalType()) {
                    case config('ecommerce.interval_type_monthly'):
                        $subscriptionEndDate = $subscriptionEndDate->addMonths($intervalCount);
                        break;

                    case config('ecommerce.interval_type_yearly'):
                        $subscriptionEndDate = $subscriptionEndDate->addYears($intervalCount);
                        break;

                    case config('ecommerce.interval_type_daily'):
                        $subscriptionEndDate = $subscriptionEndDate->addDays($intervalCount);
                        break;

                    default:
                        $format = 'Unknown subscription interval type for product id %s: %s';
                        $message = sprintf($format, $product->getId(), $product->getSubscriptionIntervalType());

                        throw new UnprocessableEntityException($message);
                        break;
                }

                $processedProductsIds[$product->getId()] = true;
            }

            $subscription->setIsActive(true);
            $subscription->setCanceledOn(null);
            $subscription->setTotalCyclesPaid($subscription->getTotalCyclesPaid() + 1);
            $subscription->setPaidUntil($subscriptionEndDate->startOfDay());
            $subscription->setUpdatedAt(Carbon::now());

            $subscriptionAccessCode = new SubscriptionAccessCode();

            $subscriptionAccessCode->setSubscription($subscription);
            $subscriptionAccessCode->setAccessCode($accessCode);
            $subscriptionAccessCode->setCreatedAt(Carbon::now());
            $subscriptionAccessCode->setUpdatedAt(Carbon::now()); // explicit date handling required for automated tests

            $this->entityManager->persist($subscriptionAccessCode);

            $this->userProductService->updateSubscriptionProducts($subscription);
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

            if($product->getSku() === 'DLM-6mo'){
                /*
                 * 6-month PDF code (sku: "DLM-6mo") purchaser will generally not be the one the one redeeming it, but
                 * their account will have DLM-6mo recorded as an purchased product. Thus that product it cannot be an
                 * edge-granting product, lest the purchaser also get access when redeemed by another user.
                 *
                 * Instead, in these cases we assign redeemer a different product that grants edge in the desired
                 * manner. Namely, for six months, but not renewing. (sku: "edge-membership-6-months")
                 *
                 * So far this is the only situation such a thing is needed, thus this hard-coded section rather than a
                 * more complicated solution.
                 *
                 * Jonathan M, Jan 2020
                 */
                $expirationDate = Carbon::now()->addMonths(6)->startOfDay();
                $product = $this->productRepository->bySku('edge-membership-6-months');
            }

            $userProduct = new UserProduct();

            $userProduct->setUser($user);
            $userProduct->setProduct($product);
            $userProduct->setQuantity(1);
            $userProduct->setExpirationDate($expirationDate);

            $this->entityManager->persist($userProduct);
            $this->entityManager->flush();

            event(new UserProductCreated($userProduct));
        }

        $accessCode->setIsClaimed(true);
        $accessCode->setClaimer($user);
        $accessCode->setClaimedOn(Carbon::now());
        $accessCode->setUpdatedAt(Carbon::now());

        $this->entityManager->persist($accessCode);
        $this->entityManager->flush();

        event(new AccessCodeClaimed($accessCode, $user, $context));

        return $accessCode;
    }
}
