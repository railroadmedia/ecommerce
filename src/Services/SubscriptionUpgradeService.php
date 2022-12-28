<?php

namespace Railroad\Ecommerce\Services;

use App\Enums\Interval;
use App\Modules\Ecommerce\Enums\DigitalAccessType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Railroad\Ecommerce\Entities\Product;
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
    private const SubscriptionNotChangedErrorMessage = "Subscription not changed:  User already has this membership type.";

    protected SubscriptionRepository $subscriptionRepository;
    protected UserProductRepository $userProductRepository;
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

    public function changeSubscription(string $accessType, string $interval, int $userId)
    {
        $isLifeTime = $this->upgradeService->isLifetimeMember($userId);
        $subscription = $this->upgradeService->getCurrentSubscription($userId);
        $currentProduct = $subscription?->getProduct();
        if ($isLifeTime) {
            if ($accessType == Product::DIGITAL_ACCESS_TYPE_ALL_CONTENT_ACCESS) {
                $product = $this->upgradeService->getLifetimeSongsProduct();
                if ($currentProduct && $currentProduct->getSku() == $product->getSku()) {
                    throw new \Exception(self::SubscriptionNotChangedErrorMessage);
                }
                return $this->orderBySku($product->getSku(), $userId);
            } else {
                if (!$subscription) {
                    throw new \Exception(self::SubscriptionNotChangedErrorMessage);
                }
                $this->upgradeService->cancelSubscription($subscription, "Cancelled for downgrade");
            }
        } else {
            if (!$subscription) {
                throw new \Exception("Active subscription does not exist");
            }
            $product = $this->upgradeService->getMembershipProduct($accessType, $interval);
            if (!$product) {
                Log::error("changeSubscription: Product does not exist: '$accessType' '$interval'");
                throw new \Exception("Product does not exist");
            }
            if ($currentProduct->getDigitalAccessType() == $product->getDigitalAccessType()
                && $currentProduct->getDigitalAccessTimeIntervalType() == $product->getDigitalAccessTimeIntervalType(
                )) {
                throw new \Exception(self::SubscriptionNotChangedErrorMessage);
            }
            return $this->orderBySku($product->getSku(), $userId);
        }
    }

    private function orderBySku(string $sku, int $userId)
    {
        //todo: where to get default payment method?
        $paymentMethodId = $this->paymentMethodRepository->getUsersPrimaryPaymentMethod($userId)?->getId();
        if ($paymentMethodId) {
            throw new \Exception("Unable to get primary payment method for user $userId");
        }
        $this->cartService->clearCart();
        $this->cartService->addToCart($sku, 1);
        $this->cartService->allowMembershipChangeDiscounts();

        $request = new OrderFormSubmitRequest(
            $this->cartService,
            $this->shippingService,
            $this->permissionService,
            $this->userProvider,
            $this->addressRepository,
            $this->customerRepository
        );
        $request["payment_method_id"] = $paymentMethodId;

        $result = $this->orderFormService->processOrderFormSubmit($request);
        $success = count($result['errors'] ?? []) == 0;
        if (!$success) {
            return implode(',', $result['errors']);
        }
        return "upgrade successful";
    }

    public function getInfo(int $userId): array
    {
        $currentMembershipTier = $this->upgradeService->getCurrentMembershipTier($userId);
        $yearUpgradeCost = $this->upgradeService->getProratedUpgradeCost($userId);
        $data = [
            "currentTier" => $currentMembershipTier->value,
            "yearUpgradeCost" => $yearUpgradeCost
        ];
        return $data;
    }
}