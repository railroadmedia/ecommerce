<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Google\Service\SecurityCommandCenter\Access;
use Illuminate\Support\Facades\Log;
use Railroad\Ecommerce\Entities\Product;
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
    case Full = 2;
}

class UpgradeService
{
    public const LifetimeSongAddOnSKU = '12345';
    public const FullTierSKUs = [
        'singeo-monthly-recurring-membership',
        UpgradeService::LifetimeSongAddOnSKU
    ];
    public const BasicTierSKUs = ['GUITAREO-1-YEAR-MEMBERSHIP'];

    protected SubscriptionRepository $subscriptionRepository;
    protected UserProductRepository $userProductRepository;
    protected ProductRepository $productRepository;

    public function __construct(
        SubscriptionRepository $subscriptionRepository,
        UserProductRepository $userProductRepository,
        ProductRepository $productRepository,
    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->userProductRepository = $userProductRepository;
        $this->productRepository = $productRepository;
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
            } elseif ($this->isFullTier($userProduct->getProduct())) {
                $membershipTier = MembershipTier::Full;
                break;
            } elseif ($this->isBasicTier($userProduct->getProduct())) {
                $membershipTier = MembershipTier::Basic;
            }
        }
        return $membershipTier;
    }

    public function getNextRenewalMembershipTier(int $userId): MembershipTier
    {
        /** @var Subscription $subscription */
        $subscription = $this->subscriptionRepository->getLatestActiveSubscriptionExcludingMobile($userId);
        $isLifeTime = $this->isLifetimeMember($userId);

        if ($isLifeTime) {
            return $subscription->getProduct()->getSku() == UpgradeService::LifetimeSongAddOnSKU
                ? MembershipTier::Full : MembershipTier::Basic;
        }
        if (!$subscription) {
            return MembershipTier::None;
        }
        if ($this->isFullTier($subscription->getProduct())) {
            return MembershipTier::Full;
        }
        return MembershipTier::Basic;
    }

    private function isFullTier(Product $product)
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

    public function getUpgradeSKU(int $userId): ?string
    {
        $isLifeTime = $this->isLifeTimeMember($userId);
        if ($isLifeTime) {
            return UpgradeService::LifetimeSongAddOnSKU;
        } else {
            return UpgradeService::FullTierSKUs[0];
        }
    }

    public function getDowngradeSKU(int $userId): ?string
    {
        return UpgradeService::BasicTierSKUs[0];
    }

    public function getDiscountAmount(Product $newProduct)
    {
        $userId = auth()->id();
        $membershipTier = $this->getCurrentMembershipTier($userId);
        $price = $newProduct->getPrice();

        switch ($membershipTier) {
            case MembershipTier::None:
                return 0; //No access, no discounts
            case MembershipTier::Basic:
                if ($this->isFullTier($newProduct)) {
                    return $this->getProratedUpgradeDiscount($newProduct, $price, $userId);
                }
                return $price; //Already have basic access, crossgrade should be free
            case MembershipTier::Full:
                return $price; //Already have full access, downgrade or crossgrades should be free
        }
    }

    public function isSubscriptionChanging(Product $product)
    {
        $userId = auth()->id();
        $membershipTier = $this->getCurrentMembershipTier($userId);
        switch ($membershipTier) {
            case MembershipTier::None:
                return false;
            case MembershipTier::Basic:
            case MembershipTier::Full:
                return true;
        }
    }

    private function getProratedUpgradeDiscount(Product $product, float $price, int $userId)
    {
        switch ($product->getDigitalAccessTimeIntervalType()) {
            case Product::DIGITAL_ACCESS_TIME_INTERVAL_TYPE_DAY:
            case Product::DIGITAL_ACCESS_TIME_INTERVAL_TYPE_MONTH:
                return $price; //not charging upgrade rate to interval types less than year
            case Product::DIGITAL_ACCESS_TIME_INTERVAL_TYPE_YEAR:
                if ($product->getSku() == UpgradeService::LifetimeSongAddOnSKU) {
                    return 0; //no discount since this is an add on for lifetime
                }
                $subscription = $this->subscriptionRepository->getLatestActiveSubscriptionExcludingMobile($userId);
                if ($subscription) {
                    $monthsUntilRenewal = $subscription->getPaidUntil()->diffInMonths(Carbon::now());
                    $finalPrice = max(($price - $subscription->getTotalPrice()) * $monthsUntilRenewal / 12, 0);
                    return $price - $finalPrice;
                }
                //todo: not sure about this case
                //user with access but no active subscription orders an upgraded membership
                //could be lifetime access, but for some reason purchases a membership?
                return 0; //no discount to be safe
            default:
                throw new \Exception(
                    "DigitalAccessTimeIntervalType '$product->getDigitalAccessTimeIntervalType()' not handled"
                );
        }
    }
}