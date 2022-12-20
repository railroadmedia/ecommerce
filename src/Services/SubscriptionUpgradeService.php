<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Entities\UserProduct;

use Railroad\Ecommerce\Repositories\UserProductRepository;
use Railroad\Ecommerce\Services\UpgradeService;
use Stripe\Service\ProductService;

use Railroad\Ecommerce\Repositories\AddressRepository;
use Railroad\Ecommerce\Repositories\CustomerRepository;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ShippingService;
use Railroad\Permissions\Services\PermissionService;
use Railroad\Ecommerce\Services\OrderFormService;
use Railroad\Ecommerce\Requests\OrderFormSubmitRequest;

class SubscriptionUpgradeService
{
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
    protected UpgradeService $upgradeService;
    protected PaymentMethodRepository $paymentMethodRepository;

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
        UpgradeService $upgradeService,
        PaymentMethodRepository $paymentMethodRepository,
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
        $this->customerRepository = $customerRepository;
        $this->upgradeService = $upgradeService;
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    public function upgrade(int $userId): string
    {
        $membershipTier = $this->upgradeService->getNextRenewalMembershipTier($userId);
        switch ($membershipTier) {
            case MembershipTier::None:
                return "Unable to upgrade user $userId, no membership access";
            case MembershipTier::Basic:
                $sku = $this->upgradeService->getUpgradeSKU($userId);
                $result = $this->orderBySku($sku, $userId);
                //cancel subscription handled through order
                $success = count($result['errors'] ?? []) == 0;
                if (!$success) {
                    return implode(',', $result['errors']);
                }
                return "upgrade successful";
            case MembershipTier::Full:
                return "Unable to upgrade user $userId, already has full access";
        }
    }

    public function downgrade(int $userId): string
    {
        $membershipTier = $this->upgradeService->getNextRenewalMembershipTier($userId);
        switch ($membershipTier) {
            case MembershipTier::None:
                return "Unable to downgrade user $userId, no membership access";
            case MembershipTier::Basic:
                return "Unable to downgrade user $userId, already has basic access";
            case MembershipTier::Full:
                if ($this->upgradeService->isLifeTimeMember($userId)) {
                    $subscriptions = $this->subscriptionRepository->getActiveSubscriptionsByUserId($userId);
                    foreach ($subscriptions as $subscription) {
                        /** @var Subscription $subscription */
                        if ($subscription->getProduct()->getSku() == UpgradeService::LifetimeSongAddOnSKU) {
                            $subscription = $this->subscriptionRepository->getLatestActiveSubscriptionExcludingMobile(
                                $userId
                            );
                            $this->cancelSubscription($subscription, "Cancelled for downgrade");
                        }
                    }
                    return "downgrade successful";
                } else {
                    $subscription = $this->subscriptionRepository->getLatestActiveSubscriptionExcludingMobile($userId);

                    $sku = $this->upgradeService->getDowngradeSKU($userId);
                    $result = $this->orderBySku($sku, $userId);
                    $success = count($result['errors'] ?? []) == 0;
                    if (!$success) {
                        return implode(',', $result['errors']);
                    }
                    $this->cancelSubscription($subscription, "Cancelled for downgrade");
                    return "downgrade successful";
                }
        }
    }

    private function orderBySku(string $sku, int $userId)
    {
        //todo: where to get default payment method?
        $paymentMethodId = $this->paymentMethodRepository->getUsersPrimaryPaymentMethod($userId)->getId();
        $this->cartService->clearCart();
        $this->cartService->addToCart($sku, 1);


        $request = new OrderFormSubmitRequest(
            $this->cartService,
            $this->shippingService,
            $this->permissionService,
            $this->userProvider,
            $this->addressRepository,
            $this->customerRepository
        );

        $request["payment_method_id"] = $paymentMethodId;

        return $this->orderFormService->processOrderFormSubmit($request);
    }

    public function getUpgradeRate(int $userId): ?float
    {
        $membershipTier = $this->upgradeService->getCurrentMembershipTier($userId);
        switch ($membershipTier) {
            case MembershipTier::None:
                return null;
            case MembershipTier::Basic:
                $sku = $this->upgradeService->getUpgradeSKU($userId);
                $product = $this->productRepository->bySku($sku);
                //Todo:  Should this include tax or discounts
                $price = $product->getPrice();
                return $this->upgradeService->getAdjustedPrice($product, $price);
            case MembershipTier::Full:
                return 0;
        }
        return $this->getAdjustedPrice($product, $price);
    }

    private function cancelSubscription(Subscription $subscription, string $cancellationReason)
    {
        $subscription->setIsActive(false);
        $subscription->setCanceledOn(Carbon::now());
        $subscription->setCancellationReason($cancellationReason);
        $this->ecommerceEntityManager->persist($subscription);
        $this->ecommerceEntityManager->flush();
    }
}