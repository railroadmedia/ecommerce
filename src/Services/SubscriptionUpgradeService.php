<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
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

use Railroad\Ecommerce\Repositories\AddressRepository;
use Railroad\Ecommerce\Repositories\CustomerRepository;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ShippingService;
use Railroad\Permissions\Services\PermissionService;
use Railroad\Ecommerce\Services\OrderFormService;
use Railroad\Ecommerce\Requests\OrderFormSubmitRequest;

enum AccessLevel: int
{
    case None = 0;
    case Basic = 1;
    case Full = 2;
}

class SubscriptionUpgradeService
{
    private const LifetimeSongAddOnSKU = '12345';
    private const FullTierSKUs = [
        'singeo-monthly-recurring-membership',
        SubscriptionUpgradeService::LifetimeSongAddOnSKUs
    ];
    private const BasicTierSKUs = ['GUITAREO-1-YEAR-MEMBERSHIP'];

    protected SubscriptionRepository $subscriptionRepository;
    protected UserProductRepository $userProductRepository;
    protected EcommerceEntityManager $ecommerceEntityManager;
    protected ProductRepository $productRepository;
    protected CartService $cartService;
    protected OrderFormService $orderFormService;
    protected ShippingService $shippingService;
    protected PermissionService $permissionService;
    protected UserProviderInterface $userProvider;
    protected AddressRepository $addressRepository;
    protected CustomerRepository $customerRepository;

    public function __construct(
        SubscriptionRepository $subscriptionRepository,
        UserProductRepository $userProductRepository,
        EcommerceEntityManager $ecommerceEntityManager,
        ProductRepository $productRepository,
        CartService $cartService,
        OrderFormService $orderFormService,
        ShippingService $shippingService,
        PermissionService $permissionService,
        UserProviderInterface $userProvider,
        AddressRepository $addressRepository,
        CustomerRepository $customerRepository,
    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->userProductRepository = $userProductRepository;
        $this->ecommerceEntityManager = $ecommerceEntityManager;
        $this->productRepository = $productRepository;
        $this->cartService = $cartService;
        $this->orderFormService = $orderFormService;
        $this->shippingService = $shippingService;
        $this->permissionService = $permissionService;
        $this->userProvider = $userProvider;
        $this->addressRepository = $addressRepository;
        $this->cusotmerRepository = $customerRepository;
    }

    private function getAccessLevel(array $userProducts): AccessLevel
    {
        $accessLevel = AccessLevel::None;

        foreach ($userProducts as $userProduct) {
            /** @var UserProduct $userProduct */
            $sku = $userProduct->getProduct()->getSku();
            if (!$userProduct->isValid()) {
                continue;
            } elseif ($this->isFullTier($userProduct->getProduct())) {
                $accessLevel = AccessLevel::Full;
                break;
            } elseif ($userProduct->getProduct()->getType() == Product::TYPE_DIGITAL_SUBSCRIPTION) {
                $accessLevel = AccessLevel::Basic;
            }
        }
        return $accessLevel;
    }

    private function isFullTier(Product $product)
    {
        return in_array($product->getSku(), SubscriptionUpgradeService::FullTierSKUs);
    }

    private function isLifetimeMember(int $userId): bool
    {
        //todo:  Pulled from DuplicateSubscriptionHandler, should be generalized or user User.is_lifetime_member somehow;
        // get all membership product skus
        $membershipProductSkus = $syncData['membership_product_skus'] ?? [];

        $userProducts = $this->userProductRepository->getAllUsersProducts($userId);
        // check if they are a lifetime member
        $userIsLifetimeMember = false;

        foreach ($userProducts as $userProduct) {
            if ($userProduct->isValid() &&
                empty($userProduct->getExpirationDate()) &&
                in_array($userProduct->getProduct()->getSku(), $membershipProductSkus)) {
                $userIsLifetimeMember = true;
            }
        }
        return $userIsLifetimeMember;
    }

    public function upgrade(int $userId): string
    {
        $userProducts = $this->userProductRepository->getAllUsersProducts($userId);
        $isLifeTime = $this->isLifeTimeMember($userId);
        $accessLevel = $this->getAccessLevel($userProducts);
        switch ($accessLevel) {
            case AccessLevel::None:
                return "Unable to upgrade user $userId, no active subscription";
            case AccessLevel::Basic:
                if ($isLifeTime) {
                    $result = $this->orderBySku(SubscriptionUpgradeService::LifetimeSongAddOnSKUs, $userId);
                } else {
                    $result = $this->orderBySku(SubscriptionUpgradeService::FullTierSKUs[0], $userId);
                }
                //cancel subscription handled through order
                return "upgrade successful";
            case AccessLevel::Full:
                return "Unable to upgrade user $userId, already has full access";
        }
    }

    private function orderBySku(string $sku, int $userId)
    {
        //todo: where to get default payment method?
        $subscription = $this->subscriptionRepository->getLatestActiveSubscriptionExcludingMobile($userId);
        $paymentMethodId = $subscription->getPaymentMethod()->getId();
        $this->cartService->clearCart();
        $this->cartService->addToCart($sku, 1);


        $request = new OrderFormSubmitRequest(
            $this->$cartService,
            $this->$shippingService,
            $this->$permissionService,
            $this->$userProvider,
            $this->$addressRepository,
            $this->$cusotmerRepository
        );

        $request["payment_method_id"] = $paymentMethodId;

        return $this->orderFormService->processOrderFormSubmit($request);
    }

    public function getUpgradeRate(int $userId): ?float
    {
        $subscription = $this->subscriptionRepository->getLatestActiveSubscriptionExcludingMobile($user->getId());
        return $this->getUpgradeRateFromSubscription($subscription, $userId);
    }

    private function getUpgradeRateFromSubscription(?Subscription $subscription, int $userId)
    {
        if ($this->isBasicTier($subscription)) {
            if ($this->isLifeTimeMember($userId)) {
                $product = $this->productRepository->bySku(SubscriptionUpgradeService::LifetimeSongAddOnSKUs);
                return $product->getPrice();
            }
        }
        return null;
    }

    public function downgrade(int $userId): string
    {
        $userProducts = $this->userProductRepository->getAllUsersProducts($userId);
        $isLifeTime = $this->isLifeTimeMember($userId);
        $accessLevel = $this->getAccessLevel($userProducts);
        switch ($accessLevel) {
            case AccessLevel::None:
                return "Unable to downgrade user $userId, no active subscription";
            case AccessLevel::Basic:
                return "Unable to downgrade user $userId, already has basic access";
            case AccessLevel::Full:
                if ($this->isLifeTimeMember($userId)) {
                    $subscriptions = $this->subscriptionRepository->getActiveSubscriptionsByUserId($userId);
                    foreach($subscriptions as $subscription){
                        /** @var Subscription $subscription */
                        if($subscription->getProduct()->getSku() == SubscriptionUpgradeService::LifetimeSongAddOnSKU){
                            $subscription = $this->subscriptionRepository->getLatestActiveSubscriptionExcludingMobile($userId);
                            $this->cancelSubscription($subscription, "Cancelled for downgrade");
                        }
                    }
                }
                else{
                    $result = $this->orderBySku(SubscriptionUpgradeService::BasicTierSKUs[0], $userId);
                    //cancel handled through order
                }
                return "downgrade successful";
        }
    }

    private function cancelSubscription(Subscription $subscription, string $cancellationReason)
    {
        $subscription->setIsActive(false);
        $subscription->setCanceledOn(Carbon::now());
        $subscription->setCancellationReason($cancellationReason);
        $this->ecommerceEntityManager->persist($subscription);
        $this->ecommerceEntityManager->flush();
    }

    public function getAdjustedPrice(Product $product, $price)
    {
        $userId = auth()->id();
        $userProducts = $this->userProductRepository->getAllUsersProducts($userId);
        $accessLevel = $this->getAccessLevel($userProducts);
        switch ($accessLevel) {
            case AccessLevel::None:
                return $price; //no adjustment required
            case AccessLevel::Basic:
                if ($this->isFullTier($product)) {
                    return $this->getProratedUpgradeRate($product, $price, $userId, $userProducts, $accessLevel);
                }
                return 0; //Already has basic access, no cost required
            case AccessLevel::Full:
                return 0; //Already have basic access, no cost required
        }
    }

    private function getProratedUpgradeRate(Product $product, $price, int $userId, array $userProducts, $accessLevel)
    {
        switch ($product->getDigitalAccessTimeIntervalType()) {
            case Product::DIGITAL_ACCESS_TIME_INTERVAL_TYPE_DAY:
            case Product::DIGITAL_ACCESS_TIME_INTERVAL_TYPE_MONTH:
                return 0; //not charging upgrade rate to interval types less than year
            case Product::DIGITAL_ACCESS_TIME_INTERVAL_TYPE_YEAR:
                if ($product->getSku() == SubscriptionUpgradeService::LifetimeSongAddOnSKU) {
                    return $price; //this will always be full price assuming the song add on is only ever used for lifetime upgrades
                }
                $subscription = $this->subscriptionRepository->getLatestActiveSubscriptionExcludingMobile($userId);
                if ($subscription) {
                    $monthsUntilRenewal = $subscription->getPaidUntil()->diffInMonths(Carbon::now());
                    $price = max($price - $subscription->getTotalPrice(), 0) * $monthsUntilRenewal / 12;
                    return $price;
                }
                //todo: not sure about this case
                //user with access but no active subscription orders an upgraded membership
                //could be lifetime access, but for some reason purchases a membership?
                return $price; //return full price to be safe
            default:
                throw new \Exception(
                    "DigitalAccessTimeIntervalType '$product->getDigitalAccessTimeIntervalType()' not handled"
                );
        }
    }
}