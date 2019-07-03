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
use Railroad\Ecommerce\Entities\PaymentTaxes;
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

        $subscription = null;

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

        if (!is_null($payment)) {
            $this->populatePaymentTaxes($payment, $cart, $subscription);
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

            $subscriptionPricePerPayment = $product->getPrice();

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
     * @param Subscription|null $subscription
     *
     * @return PaymentTaxes|null
     *
     * @throws Throwable
     */
    public function populatePaymentTaxes(
        Payment $payment,
        Cart $cart,
        ?Subscription $subscription = null
    ): PaymentTaxes
    {
        $paymentTaxes = new PaymentTaxes();

        $paymentTaxes->setPayment($payment);

        $address = $this->taxService->getAddressForTaxation($cart);

        $totalItemCostDue = 0;

        if (is_null($subscription) || $cart->getPaymentPlanNumberOfPayments() > 1) {
            $totalItemCostDue = $this->cartService->getTotalItemCosts();
        } else {
            // the DiscountService::SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE type discount is applied here in OrderClaimingService, not in cart service
            // the resulting product due needs to match subscription
            $totalItemCostDue = $subscription->getTotalPrice() - $subscription->getTax();
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