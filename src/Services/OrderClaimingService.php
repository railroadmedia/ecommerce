<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\OrderDiscount;
use Railroad\Ecommerce\Entities\OrderItem;
use Railroad\Ecommerce\Entities\OrderPayment;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\Purchaser;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\SubscriptionPayment;
use Railroad\Ecommerce\Events\OrderEvent;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionCreated;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Throwable;

class OrderClaimingService
{
    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var DiscountService
     */
    private $discountService;

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
     * OrderClaimingService constructor.
     *
     * @param CartService $cartService
     * @param DiscountService $discountService
     * @param ShippingService $shippingService
     * @param EcommerceEntityManager $entityManager
     * @param TaxService $taxService
     */
    public function __construct(
        CartService $cartService,
        DiscountService $discountService,
        ShippingService $shippingService,
        EcommerceEntityManager $entityManager,
        TaxService $taxService
    )
    {
        $this->cartService = $cartService;
        $this->discountService = $discountService;
        $this->shippingService = $shippingService;
        $this->entityManager = $entityManager;
        $this->taxService = $taxService;
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
        $taxesDue =
            !is_null($cart->getTaxOverride()) ? $cart->getTaxOverride() : $this->cartService->getTaxDueForOrder();

        // create the order
        $order = new Order();

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
            }
        }

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

        $this->entityManager->flush();

        event(new OrderEvent($order, $payment));

        // product access via event?

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

        if (is_null($orderItem)) {

            $nextBillDate =
                Carbon::now()
                    ->addMonths(1);

            $type = config('ecommerce.type_payment_plan');

            $subscriptionPricePerPayment = $this->cartService->getDueForOrder();
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

            $subscriptionPricePerPayment = $product->getPrice();

            foreach ($orderItem->getOrderItemDiscounts() as $orderItemDiscount) {

                $discount = $orderItemDiscount->getDiscount();

                if ($discount->getType() == DiscountService::SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE) {
                    $nextBillDate = $nextBillDate->addDays($discount->getAmount());

                }
                elseif ($discount->getType() == DiscountService::SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE) {
                    // todo - confirm for subscriptions only SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE modifies the cost
                    $subscriptionPricePerPayment =
                        $orderItem->getProduct()
                            ->getPrice() - $discount->getAmount();
                }
            }
        }

        $subscription = new Subscription();

        $intervalType = $product ? $product->getSubscriptionIntervalType() : config('ecommerce.interval_type_monthly');

        $intervalCount = $product ? $product->getSubscriptionIntervalCount() : 1;

        $totalTaxDue =
            !is_null(
                $this->cartService->getCart()
                    ->getTaxOverride()
            ) ?
                $this->cartService->getCart()
                    ->getTaxOverride() : $this->taxService->getTaxesDueTotal(
                $this->cartService->getTotalItemCosts(),
                0,
                $this->taxService->getAddressForTaxation($this->cartService->getCart())
            );

        $subscription->setBrand($purchaser->getBrand());
        $subscription->setType($type);
        $subscription->setUser($purchaser->getUserObject());
        $subscription->setOrder($order);
        $subscription->setProduct(is_null($orderItem) ? null : $orderItem->getProduct());
        $subscription->setIsActive(true);
        $subscription->setStartDate(Carbon::now());
        $subscription->setPaidUntil($nextBillDate);
        $subscription->setTotalPrice($subscriptionPricePerPayment + $totalTaxDue);
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

        $this->entityManager->persist($subscriptionPayment);

        return $subscription;
    }
}