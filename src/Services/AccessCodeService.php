<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Datetime;
use Illuminate\Database\DatabaseManager;
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

            if($subscription->getType() === 'payment plan'){
                continue;
            }

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

                // only extend their membership subscription if the code being claimed is for a membership product
                if (!$product->isMembershipProduct()) {
                    continue;
                }

                $intervalCount = $product->getDigitalAccessTimeIntervalLength();

                switch ($product->getDigitalAccessTimeIntervalType()) {
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
                        $message = sprintf($format, $product->getId(), $product->getDigitalAccessTimeIntervalType());

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
        foreach ($accessCodeProducts as $accessCodeProduct) {

            $currentMembershipUserProducts = [];

            /*
             * Do not process here if already processed in subscription-handling loop above. This is for cases where
             * a user does not have access via a subscription. Typically this is either a user who does not yet have
             * access, or a user who's access is from a previously-redeemed access-code. In the case of the latter, what
             * was happening before previously is that rather than add to their access time, another user product would
             * be added that duplicated their already access but failed to extend their access.
             *
             * Jonathan M, Nov 2020
             */
            if (isset($processedProductsIds[$accessCodeProduct->getId()])) {
                continue;
            }

            $codeRedeemProductHackMap = config('ecommerce.code_redeem_product_sku_swap', []);
            if(array_key_exists($accessCodeProduct->getSku(), $codeRedeemProductHackMap)){
                $replaceWithSku = $codeRedeemProductHackMap[$accessCodeProduct->getSku()];
                $accessCodeProduct = $this->productRepository->bySku($replaceWithSku);
            }

            $usersProducts = $this->userProductService->getAllUsersProducts($user->getId());

            $membershipProductSkus = config('ecommerce.membership_product_skus_for_code_redeem', []);

            foreach($usersProducts as $userProduct){

                $userProductSku = $userProduct->getProduct()->getSku();

                if($userProduct->getProduct()->isMembershipProduct()){

                    $userProductExpirationDate = Carbon::parse($userProduct->getExpirationDate());

                    $now = Carbon::now();

                    if(empty($userProductExpirationDate)) {
                        continue;
                    }

                    if($userProductExpirationDate->greaterThan($now)){
                        $currentMembershipUserProducts[] = $userProduct;
                    }
                }
            }

            // order by expiry date descending
            usort($currentMembershipUserProducts, function($a, $b) {
                /** @var $a UserProduct */
                /** @var $b UserProduct */
                $aDateTime = new Datetime($a->getExpirationDate());
                $bDateTime = new Datetime($b->getExpirationDate());
                return $aDateTime < $bDateTime;
            });

            $userProduct = reset($currentMembershipUserProducts);

            $expirationDate = Carbon::now();

            if(!empty($userProduct)){
                /** @var UserProduct $userProduct */
                $expirationDate = $userProduct->getExpirationDate();
            }

            if(empty($userProduct)){
                $userProduct = new UserProduct();
                $userProduct->setUser($user);
                $userProduct->setProduct($accessCodeProduct);
                $userProduct->setQuantity(1);
            }

            $intervalCount = $accessCodeProduct->getDigitalAccessTimeIntervalLength();

            switch ($accessCodeProduct->getDigitalAccessTimeIntervalType()) {
                case config('ecommerce.interval_type_monthly'):
                    $expirationDate = $expirationDate->addMonths($intervalCount)->startOfDay();
                    break;

                case config('ecommerce.interval_type_yearly'):
                    $expirationDate = $expirationDate->addYears($intervalCount)->startOfDay();
                    break;

                case config('ecommerce.interval_type_daily'):
                    $expirationDate = $expirationDate->addDays($intervalCount)->startOfDay();
                    break;

                case null:
                    $expirationDate = null;
                    break;

                default:
                    $format = 'Unknown subscription interval type for product id %s: %s';
                    $message = sprintf($format, $accessCodeProduct->getId(), $accessCodeProduct->getDigitalAccessTimeIntervalType());

                    throw new UnprocessableEntityException($message);
                    break;
            }

            if(!is_null($expirationDate)){
                if(get_class($expirationDate) !== Carbon::class){
                    $type = gettype($expirationDate);
                    if($type === 'object'){
                        $type = 'object of class "' . get_class($expirationDate) . '"")';
                    }
                    $message = '$expirationDate unexpected type of ' . $type . ' for user ' . $user->getId() .
                        ' redeeming code ' . $rawAccessCode;
                    throw new UnprocessableEntityException($message);
                    break;
                }
                $userProduct->setExpirationDate($expirationDate->copy());
            }

            $this->entityManager->persist($userProduct);
            $this->entityManager->flush();

//            $_foo_b_after = app(DatabaseManager::class)->table('ecommerce_user_products')->get();

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

    /**
     * @param  array  $productIds
     * @param  string  $brand
     * @return AccessCode
     */
    public function generateAccessCode(array $productIds, $brand)
    {
        $accessCode = new AccessCode();
        $accessCode->setProductIds($productIds);
        $accessCode->setBrand($brand);

        $accessCode->generateCode();

        $this->entityManager->persist($accessCode);
        $this->entityManager->flush();

        return $accessCode;
    }

    /**
     * Turns: fcbd53d4b41b3264249a713e
     * Into: fcbd - 53d4 - b41b - 3264 - 249a - 713e
     * @param $code
     * @return string
     */
    public function hyphenateCode($code)
    {
        return implode(" - ", str_split($code, 4));
    }
}
