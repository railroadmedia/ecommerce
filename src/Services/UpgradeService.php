<?php

namespace Railroad\Ecommerce\Services;

use App\Enums\Interval;
use Carbon\Carbon;
use Google\Service\SecurityCommandCenter\Access;
use Illuminate\Support\Facades\Log;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Entities\UserProduct;

use Railroad\Ecommerce\Repositories\UserProductRepository;
use Stripe\Service\ProductService;
use Railroad\Ecommerce\Services\OrderFormService;
use Railroad\Ecommerce\Requests\OrderFormSubmitRequest;

enum MembershipTier: int
{
    case None = 0;
    case Basic = 1;
    case Plus = 2;
}

class UpgradeService
{
    public const LifetimeSongAddOnSKU = '12345';
    public const MusoraProductBrand = 'musora';

    protected SubscriptionRepository $subscriptionRepository;
    protected UserProductRepository $userProductRepository;
    protected ProductRepository $productRepository;
    protected EcommerceEntityManager $ecommerceEntityManager;

    public function __construct(
        SubscriptionRepository $subscriptionRepository,
        UserProductRepository $userProductRepository,
        ProductRepository $productRepository,
        EcommerceEntityManager $ecommerceEntityManager,
    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->userProductRepository = $userProductRepository;
        $this->productRepository = $productRepository;
        $this->ecommerceEntityManager = $ecommerceEntityManager;
    }

    public function getCurrentMembershipTier(int $userId): MembershipTier
    {
        $userProducts = $this->userProductRepository->getAllUsersProducts($userId);
        $membershipTier = MembershipTier::None;

        foreach ($userProducts as $userProduct) {
            /** @var UserProduct $userProduct */
            $sku = $userProduct->getProduct()->getSku();
            if (!$userProduct->isValid()) {
                continue;
            } elseif ($this->isPlusTier($userProduct->getProduct())) {
                $membershipTier = MembershipTier::Plus;
                break;
            } elseif ($this->isBasicTier($userProduct->getProduct())) {
                $membershipTier = MembershipTier::Basic;
            }
        }
        return $membershipTier;
    }

    private function isPlusTier(Product $product)
    {
        return $product->getDigitalAccessType() == Product::DIGITAL_ACCESS_TYPE_ALL_CONTENT_ACCESS;
    }

    private function isBasicTier(Product $product)
    {
        return $product->getDigitalAccessType() == Product::DIGITAL_ACCESS_TYPE_BASIC_CONTENT_ACCESS;
    }

    public function isLifetimeMember(int $userId): bool
    {
        return false;
        //todo:  Pulled from DuplicateSubscriptionHandler, should be generalized or user User.is_lifetime_member somehow;
        // get all membership product skus


        $userProducts = $this->userProductRepository->getAllUsersProducts($userId);
        // check if they are a lifetime member
        $userIsLifetimeMember = false;

        foreach (config('ecommerce.membership_product_syncing_info', []) as $brand => $syncData) {
            $membershipProductSkus = $syncData['membership_product_skus'] ?? [];
            if (empty($membershipProductSkus)) {
                continue;
            }
            foreach ($userProducts as $userProduct) {
                if ($userProduct->isValid() &&
                    empty($userProduct->getExpirationDate()) &&
                    in_array($userProduct->getProduct()->getSku(), $membershipProductSkus)) {
                    $userIsLifetimeMember = true;
                    break;
                }
            }
        }
        return $userIsLifetimeMember;
    }

    public function getLifetimeSongsProduct(): ?Product
    {
        return $this->productRepository->bySku(UpgradeService::LifetimeSongAddOnSKU);
    }

    public function getMembershipProduct(string $digitalAccessType, string $interval): ?Product
    {
        $product = $this->productRepository
            ->getMembershipProduct(self::MusoraProductBrand, $digitalAccessType, $interval);
        return $product;
    }

    public function isMembershipChanging(Product $product, ?Subscription $subscription)
    {
        if (!$subscription || !$product->isMembershipProduct()) {
            return false;
        }
        return $subscription->getProduct()->getSku() != $product->getSku();
    }

    public function getDiscountAmount(Product $newProduct)
    {
        if ($newProduct->getType() != Product::TYPE_DIGITAL_SUBSCRIPTION) {
            return 0;  //no discount
        }
        $userId = auth()->id();
        $membershipTier = $this->getCurrentMembershipTier($userId);
        $price = $newProduct->getPrice();

        switch ($membershipTier) {
            case MembershipTier::None:
                return 0; //No access, no discounts
            case MembershipTier::Basic:
                if ($this->isPlusTier($newProduct)) {
                    if ($newProduct->getSku() == UpgradeService::LifetimeSongAddOnSKU) {
                        return 0; //no discount
                    }
                    $upgradePrice = $this->getProratedUpgradeCost($userId);
                    if (!is_null($upgradePrice)) {
                        return $price - $upgradePrice;
                    }
                    return 0; //no discount
                }
                return $price; //Already have basic access, crossgrade should be free
            case MembershipTier::Plus:
                return $price; //Already have plus access, downgrade or crossgrades should be free
        }
    }

    public function getProratedUpgradeCost(int $userId): ?float
    {
        $membershipTier = $this->getCurrentMembershipTier($userId);
        if ($membershipTier != MembershipTier::Basic) {
            return null;
        }

        $subscription = $this->getCurrentSubscription($userId);
        $product = $subscription->getProduct();
        if (!$product) {
            return null;
        }

        switch ($product->getDigitalAccessTimeIntervalType()) {
            case Product::DIGITAL_ACCESS_TIME_INTERVAL_TYPE_DAY:
            case Product::DIGITAL_ACCESS_TIME_INTERVAL_TYPE_MONTH:
                return 0; //not charging upgrade rate to interval types less than year
            case Product::DIGITAL_ACCESS_TIME_INTERVAL_TYPE_YEAR:
                $yearPlusMembershipProduct = $this->getMembershipProduct(
                    Product::DIGITAL_ACCESS_TYPE_ALL_CONTENT_ACCESS,
                    Product::DIGITAL_ACCESS_TIME_INTERVAL_TYPE_YEAR
                );
                $monthsUntilRenewal = $subscription->getPaidUntil()->diffInMonths(Carbon::now());
                $price = round(
                    max(
                        ($yearPlusMembershipProduct->getPrice() - $subscription->getTotalPrice(
                            )) * $monthsUntilRenewal / 12,
                        0
                    ),
                    2
                );

                return $price;
            default:
                throw new \Exception(
                    "DigitalAccessTimeIntervalType '$product->getDigitalAccessTimeIntervalType()' not handled"
                );
        }
    }

    public function getCurrentSubscription(int $userId): ?Subscription
    {
        $subscription = $this->subscriptionRepository->getLatestActiveSubscriptionExcludingMobile($userId);
        return $subscription;
    }

    public function cancelSubscription(Subscription $subscription, string $cancellationReason)
    {
        $subscription->setIsActive(false);
        $subscription->setCanceledOn(Carbon::now());
        $subscription->setCancellationReason($cancellationReason);
        $this->ecommerceEntityManager->persist($subscription);
        $this->ecommerceEntityManager->flush();
    }
}