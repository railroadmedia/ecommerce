<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Railroad\ActionLog\Services\ActionLogService;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\OrderDiscount;
use Railroad\Ecommerce\Entities\OrderItem;
use Railroad\Ecommerce\Entities\OrderPayment;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\PaymentTaxes;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\Purchaser;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\SubscriptionPayment;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Events\OrderEvent;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionCreated;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Permissions\Services\PermissionService;
use Throwable;

class OrderClaimingService
{
    /**
     * @var ActionLogService
     */
    private $actionLogService;

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var DiscountService
     */
    private $discountService;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var ShippingService
     */
    private $shippingService;

    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var TaxService
     */
    private $taxService;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * OrderClaimingService constructor.
     *
     * @param ActionLogService $actionLogService
     * @param CartService $cartService
     * @param DiscountService $discountService
     * @param ShippingService $shippingService
     * @param EcommerceEntityManager $entityManager
     * @param PermissionService $permissionService
     * @param TaxService $taxService
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        ActionLogService $actionLogService,
        CartService $cartService,
        DiscountService $discountService,
        ShippingService $shippingService,
        EcommerceEntityManager $entityManager,
        PermissionService $permissionService,
        TaxService $taxService,
        UserProviderInterface $userProvider
    )
    {
        $this->actionLogService = $actionLogService;
        $this->cartService = $cartService;
        $this->discountService = $discountService;
        $this->shippingService = $shippingService;
        $this->permissionService = $permissionService;
        $this->entityManager = $entityManager;
        $this->taxService = $taxService;
        $this->userProvider = $userProvider;
    }

    /**
     * @param Purchaser $purchaser
     * @param Payment|null $payment
     * @param Cart $cart
     * @param Address $shippingAddress
     *
     * @return Order
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Throwable
     */
    public function claimOrder(Purchaser $purchaser, ?Payment $payment, Cart $cart, ?Address $shippingAddress): Order
    {
        $this->cartService->setCart($cart);

        $totalItemsCosts = $this->cartService->getTotalItemCosts();
        $shippingCosts =
            !is_null($cart->getShippingOverride()) ? $cart->getShippingOverride() :
                $this->shippingService->getShippingDueForCart($cart, $totalItemsCosts);

        $taxableAddress = $this->taxService->getAddressForTaxation($cart);

        $productTaxDue =
            !is_null($cart->getProductTaxOverride()) ? $cart->getProductTaxOverride() :
                $this->taxService->getTaxesDueForProductCost(
                    $totalItemsCosts,
                    $taxableAddress
                );

        $shippingTaxDue =
            !is_null($cart->getShippingTaxOverride()) ? $cart->getShippingTaxOverride() :
                $this->taxService->getTaxesDueForShippingCost(
                    $shippingCosts,
                    $taxableAddress
                );

        $taxesDue = round($productTaxDue + $shippingTaxDue, 2);

        // create the order
        $order = new Order();

        $currentUser = $this->userProvider->getCurrentUser();

        if ($currentUser &&
            $this->permissionService->can(auth()->id(), 'place-orders-for-other-users') &&
            $currentUser->getId() != $purchaser->getId()) {

            $order->setPlacedByUser($currentUser);
        }

        $order->setTotalDue($this->cartService->getDueForOrder());
        $order->setProductDue($totalItemsCosts);
        $order->setFinanceDue($this->cartService->getTotalFinanceCosts());
        $order->setTaxesDue($taxesDue);
        $order->setTotalPaid($this->cartService->getDueForInitialPayment());
        $order->setBrand($purchaser->getBrand());
        $order->setNote(request()->get('note'));
        $order->setUser($purchaser->getType() == Purchaser::USER_TYPE ? $purchaser->getUserObject() : null);
        $order->setCustomer($purchaser->getType() == Purchaser::CUSTOMER_TYPE ? $purchaser->getCustomerEntity() : null);
        $order->setShippingDue($shippingCosts);
        $order->setShippingAddress($shippingAddress);

        if (!empty($payment)) {
            $order->setBillingAddress(
                $payment->getPaymentMethod()
                    ->getBillingAddress()
            );
        }

        $order->setCreatedAt(Carbon::now());

        // link the payment
        if (!empty($payment)) {
            $orderPayment = new OrderPayment();

            $orderPayment->setOrder($order);
            $orderPayment->setPayment($payment);
            $orderPayment->setCreatedAt(Carbon::now());

            $this->entityManager->persist($orderPayment);
        }

        if ($shippingAddress) {
            $this->entityManager->persist($shippingAddress);
        }

        $this->entityManager->persist($order);

        // create the order items
        $orderItems = $this->cartService->getOrderItemEntities();
        $subscriptions = [];

        foreach ($orderItems as $orderItem) {

            $order->addOrderItem($orderItem);

            foreach ($orderItem->getOrderItemDiscounts() as $orderItemDiscount) {
                $orderItemDiscount->setOrder($order);
                $this->entityManager->persist($orderItemDiscount);
            }

            $this->entityManager->persist($orderItem);

            // create product subscriptions
            if ($orderItem->getProduct()
                    ->getType() == Product::TYPE_DIGITAL_SUBSCRIPTION && !empty($payment)) {

                $subscription = $this->createSubscription(
                    $purchaser,
                    $payment,
                    $order,
                    $orderItem
                );

                $this->entityManager->persist($subscription);

                event(new SubscriptionCreated($subscription));

                $subscriptions[$orderItem->getProduct()->getSku()] = $subscription;
            }
        }

        $subscription = null;

        // create the order discounts
        $orderDiscounts = $this->discountService->getOrderDiscounts($cart, $totalItemsCosts, $shippingCosts);

        foreach ($orderDiscounts as $discount) {
            $orderDiscount = new OrderDiscount();

            $orderDiscount->setOrder($order);
            $orderDiscount->setDiscount($discount);

            $this->entityManager->persist($orderDiscount);
        }

        // create the payment plan subscription if required
        if ($cart->getPaymentPlanNumberOfPayments() > 1 && !empty($payment)) {

            $subscription = $this->createSubscription(
                $purchaser,
                $payment,
                $order,
                null,
                $cart->getPaymentPlanNumberOfPayments()
            );

            $this->entityManager->persist($subscription);

            event(new SubscriptionCreated($subscription));
        }

        if (!is_null($payment)) {
            $this->populatePaymentTaxes($payment, $cart, $orderItems, $subscriptions);
        }

        $this->entityManager->flush();

        // entities ids are needed for the next block
        $actionName = ActionLogService::ACTION_CREATE;
        $actor = $actorId = $actorRole = null;
        $brand = $purchaser->getBrand();

        if ($purchaser->getType() == Purchaser::USER_TYPE && !empty($purchaser->getId())) {

            /** @var $currentUser User */
            $currentUser = $this->userProvider->getCurrentUser();

            /** @var $purchaserUser User */
            $purchaserUser = $purchaser->getUserObject();

            $actor = $currentUser->getEmail();
            $actorId = $currentUser->getId();
            $actorRole = $currentUser->getId() == $purchaserUser->getId() ? ActionLogService::ROLE_USER : ActionLogService::ROLE_ADMIN;
        } else {
            $customer = $purchaser->getCustomerEntity();

            $actor = $customer->getEmail();
            $actorId = $customer->getId();
            $actorRole = ActionLogService::ROLE_CUSTOMER;
        }

        if (!is_null($subscription)) {
            $this->actionLogService->recordAction($brand, $actionName, $subscription, $actor, $actorId, $actorRole);
        }

        if (!empty($subscriptions)) {
            foreach ($subscriptions as $sub) {
                $this->actionLogService->recordAction($brand, $actionName, $sub, $actor, $actorId, $actorRole);
            }
        }

        event(new OrderEvent($order, $payment));

        return $order;
    }

    /**
     * @param Purchaser $purchaser
     * @param Payment $payment
     * @param Order $order
     * @param OrderItem|null $orderItem
     * @param int|null $totalCyclesDue
     *
     * @return Subscription
     *
     * @throws Throwable
     */
    public function createSubscription(
        Purchaser $purchaser,
        Payment $payment,
        Order $order,
        ?OrderItem $orderItem,
        int $totalCyclesDue = null
    ): Subscription
    {
        $type = Subscription::TYPE_SUBSCRIPTION;

        $nextBillDate = null;
        $subscriptionPricePerPayment = 0;
        $product = null;

        // for payment plans, the taxable amount is total items costs
        // for normal subscriptions, the taxable amount is subscription product price, with any discounts applied
        $subscriptionTaxableAmount = 0;

        if (is_null($orderItem)) {

            $nextBillDate =
                Carbon::now()
                    ->addMonths(1);

            $type = config('ecommerce.type_payment_plan');

            $subscriptionPricePerPayment = $this->cartService->getDueForPaymentPlanPayments();

            $subscriptionTaxableAmount = $this->cartService->getTotalItemCosts();
        }
        else {

            $product = $orderItem->getProduct();

            if (!empty($product->getSubscriptionIntervalType())) {
                if ($product->getSubscriptionIntervalType() == config('ecommerce.interval_type_monthly')) {
                    $nextBillDate =
                        Carbon::now()
                            ->addMonths($product->getSubscriptionIntervalCount());

                }
                elseif ($product->getSubscriptionIntervalType() == config('ecommerce.interval_type_yearly')) {
                    $nextBillDate =
                        Carbon::now()
                            ->addYears($product->getSubscriptionIntervalCount());

                }
                elseif ($product->getSubscriptionIntervalType() == config('ecommerce.interval_type_daily')) {
                    $nextBillDate =
                        Carbon::now()
                            ->addDays($product->getSubscriptionIntervalCount());
                }
            }

            $subscriptionPricePerPayment = $orderItem->getFinalPrice();

            foreach ($orderItem->getOrderItemDiscounts() as $orderItemDiscount) {

                $discount = $orderItemDiscount->getDiscount();

                if ($discount->getType() == DiscountService::SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE) {
                    $nextBillDate = $nextBillDate->addDays($discount->getAmount());

                }
                elseif ($discount->getType() == DiscountService::SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE) {
                    // todo - confirm for subscriptions only SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE modifies the cost
                    $subscriptionPricePerPayment =
                        round($orderItem->getProduct()
                            ->getPrice() - $discount->getAmount(), 2);
                }
            }

            $subscriptionTaxableAmount = $subscriptionPricePerPayment;
        }

        $subscription = new Subscription();

        $intervalType = $product ? $product->getSubscriptionIntervalType() : config('ecommerce.interval_type_monthly');

        $intervalCount = $product ? $product->getSubscriptionIntervalCount() : 1;

        $taxableAddress = $this->taxService->getAddressForTaxation($this->cartService->getCart());

        $totalTaxDue =
            !is_null($this->cartService->getCart()->getProductTaxOverride()) ? $this->cartService->getCart()->getProductTaxOverride() :
                $this->taxService->getTaxesDueForProductCost(
                    $subscriptionTaxableAmount,
                    $taxableAddress
                );

        $subscription->setBrand($purchaser->getBrand());
        $subscription->setType($type);
        $subscription->setUser($purchaser->getUserObject());
        $subscription->setOrder($order);
        $subscription->setProduct(is_null($orderItem) ? null : $orderItem->getProduct());
        $subscription->setIsActive(true);
        $subscription->setStartDate(Carbon::now());
        $subscription->setPaidUntil($nextBillDate);
        $subscription->setTotalPrice(round($subscriptionPricePerPayment + $totalTaxDue, 2));
        $subscription->setTax($totalTaxDue);
        $subscription->setCurrency($payment->getCurrency());
        $subscription->setIntervalType($intervalType);
        $subscription->setIntervalCount($intervalCount);
        $subscription->setTotalCyclesPaid(1);
        $subscription->setTotalCyclesDue($totalCyclesDue);
        $subscription->setPaymentMethod($payment->getPaymentMethod());
        $subscription->setCreatedAt(Carbon::now());

        $subscriptionPayment = new SubscriptionPayment();

        $subscriptionPayment->setSubscription($subscription);
        $subscriptionPayment->setPayment($payment);

        $this->entityManager->persist($subscription);
        $this->entityManager->persist($subscriptionPayment);

        $this->entityManager->flush();

        return $subscription;
    }

    /**
     * @param Payment $payment
     * @param Cart $cart
     * @param array $orderItems
     * @param array $subscriptions
     *
     * @return PaymentTaxes|null
     *
     * @throws Throwable
     */
    public function populatePaymentTaxes(
        Payment $payment,
        Cart $cart,
        array $orderItems,
        array $subscriptions
    ): PaymentTaxes
    {
        $paymentTaxes = new PaymentTaxes();

        $paymentTaxes->setPayment($payment);

        $address = $this->taxService->getAddressForTaxation($cart);

        $totalItemCostDue = 0;

        if (empty($subscriptions)) {
            $totalItemCostDue = $this->cartService->getTotalItemCosts();
        } else {
            foreach ($orderItems as $orderItem) {
                if ($orderItem->getProduct()->getType() == Product::TYPE_DIGITAL_SUBSCRIPTION) {

                    // the DiscountService::SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE type discount is applied here in OrderClaimingService, not in cart service
                    // the resulting product due needs to match subscription

                    $orderItemSubscription = $subscriptions[$orderItem->getProduct()->getSku()];
                    $totalItemCostDue +=  $orderItemSubscription->getTotalPrice() - $orderItemSubscription->getTax();
                } else {
                    $totalItemCostDue += $orderItem->getFinalPrice();
                }
            }
        }

        $shippingDue =
            !is_null($cart->getShippingOverride()) ? $cart->getShippingOverride() :
                $this->shippingService->getShippingDueForCart($cart, $totalItemCostDue);

        $paymentTaxes->setCountry($address->getCountry());
        $paymentTaxes->setRegion($address->getRegion());
        $paymentTaxes->setProductRate(
            $this->taxService->getProductTaxRate($address)
        );
        $paymentTaxes->setShippingRate(
            $this->taxService->getShippingTaxRate($address)
        );

        $productTaxDue =
            !is_null($cart->getProductTaxOverride()) ? $cart->getProductTaxOverride() :
                $this->taxService->getTaxesDueForProductCost(
                    $totalItemCostDue,
                    $address
                );

        $shippingTaxDue =
            !is_null($cart->getShippingTaxOverride()) ? $cart->getShippingTaxOverride() :
                $this->taxService->getTaxesDueForShippingCost(
                    $shippingDue,
                    $address
                );

        $paymentTaxes->setProductTaxesPaid($productTaxDue);
        $paymentTaxes->setShippingTaxesPaid($shippingTaxDue);

        $this->entityManager->persist($paymentTaxes);

        return $paymentTaxes;
    }
}