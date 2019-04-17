<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\OrderPayment;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\Purchaser;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

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
     * OrderClaimingService constructor.
     *
     * @param CartService $cartService
     * @param DiscountService $discountService
     * @param ShippingService $shippingService
     * @param EcommerceEntityManager $entityManager
     */
    public function __construct(
        CartService $cartService,
        DiscountService $discountService,
        ShippingService $shippingService,
        EcommerceEntityManager $entityManager
    )
    {
        $this->cartService = $cartService;
        $this->discountService = $discountService;
        $this->shippingService = $shippingService;
        $this->entityManager = $entityManager;
    }

    /**
     * @param Purchaser $purchaser
     * @param Payment $payment
     * @param Cart $cart
     *
     * @return Order
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Throwable
     */
    public function claimOrder(Purchaser $purchaser, Payment $payment, Cart $cart)
    {
        $this->cartService->setCart($cart);

        $totalItemsCosts = $this->cartService->getTotalItemCosts();
        $shippingCosts = $this->shippingService->getShippingDueForCart($cart, $totalItemsCosts);

        // create the order
        $order = new Order();

        $order->setTotalDue($this->cartService->getDueForOrder())
            ->setProductDue($totalItemsCosts)
            ->setFinanceDue($this->cartService->getTotalFinanceCosts())
            ->setTaxesDue($this->cartService->getTaxDueForOrder())
            ->setTotalPaid($payment->getTotalPaid())
            ->setBrand($purchaser->getBrand())
            ->setUser($purchaser->getType() == Purchaser::USER_TYPE ? $purchaser->getUserObject() : null)
            ->setCustomer($purchaser->getType() == Purchaser::USER_TYPE ? $purchaser->getCustomerEntity() : null)
            ->setShippingDue($shippingCosts)
            ->setShippingAddress(
                $cart->getShippingAddress()
                    ->toEntity()
            )
            ->setBillingAddress(
                $payment->getPaymentMethod()
                    ->getBillingAddress()
            )
            ->setCreatedAt(Carbon::now());

        // link the payment
        $orderPayment = new OrderPayment();

        $orderPayment->setOrder($order)
            ->setPayment($payment)
            ->setCreatedAt(Carbon::now());

        $this->entityManager->persist($order);
        $this->entityManager->persist($orderPayment);

        // create the order items
        $orderItems = $this->cartService->getOrderItemEntities();

        foreach ($orderItems as $orderItem) {

            $orderItem->setOrder($order);

            foreach ($orderItem->getOrderItemDiscounts() as $orderItemDiscount) {
                $orderItemDiscount->setOrder($order);
                $this->entityManager->persist($orderItemDiscount);
            }

            $this->entityManager->persist($orderItem);

            // create product subscriptions
            if ($orderItem->getProduct()->getType() == ConfigService::$typeSubscription) {

                $subscription = $this->createSubscription(
                    $purchaser,
                    $payment,
                    $order,
                    $orderItem
                );
            }
        }

        // create the order discounts
        $orderDiscounts = $this->discountService->getOrderDiscounts($cart, $totalItemsCosts, $shippingCosts);

        foreach ($orderDiscounts as $discount) {
            $orderDiscount = new OrderDiscount();

            $orderDiscount->setOrder($order)
                ->setDiscount($discount);

            $this->entityManager->persist($orderItemDiscount);
        }

        // create the payment plan subscription if required
        if ($cart->getPaymentPlanNumberOfPayments() > 1) {

            $subscription = $this->createSubscription(
                $purchaser,
                $payment,
                $order,
                null,
                $cart->getPaymentPlanNumberOfPayments()
            );
        }

        // order shipping fulfilment via event

        // create user product via event

        // product access via event?

        $this->entityManager->flush();
    }

    public function createSubscription(
        Purchaser $purchaser,
        Payment $payment,
        Order $order,
        ?OrderItem $orderItem,
        int $totalCyclesDue = null
    ): Subscription
    {
        $type = ConfigService::$typeSubscription;

        $nextBillDate = null;
        $subscriptionPricePerPayment = 0;

        if (is_null($orderItem)) {

            $nextBillDate =
                Carbon::now()
                    ->addMonths(1);

            $type = ConfigService::$paymentPlanType;

            $subscriptionPricePerPayment = $this->cartService->getDueForOrder();
        }
        else {

            $product = $orderItem->getProduct();

            if (!empty($product->getSubscriptionIntervalType())) {
                if ($product->getSubscriptionIntervalType() == ConfigService::$intervalTypeMonthly) {
                    $nextBillDate =
                        Carbon::now()
                            ->addMonths($product->getSubscriptionIntervalCount());

                }
                elseif ($product->getSubscriptionIntervalType() == ConfigService::$intervalTypeYearly) {
                    $nextBillDate =
                        Carbon::now()
                            ->addYears($product->getSubscriptionIntervalCount());

                }
                elseif ($product->getSubscriptionIntervalType() == ConfigService::$intervalTypeDaily) {
                    $nextBillDate =
                        Carbon::now()
                            ->addDays($product->getSubscriptionIntervalCount());
                }
            }

            $subscriptionPricePerPayment = $product->getPrice();

            foreach ($orderItem->getOrderItemDiscounts() as $orderItemDiscount) {

                if ($orderItemDiscount->getType() == DiscountService::SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE) {
                    $nextBillDate = $nextBillDate->addDays($discount->getAmount());

                }
                elseif ($discount->getType() == DiscountService::SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE) {
                    // todo - confirm for subscriptions only SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE modifies the cost
                    $subscriptionPricePerPayment = $orderItem->getProduct()->getPrice() - $discount->getAmount();
                }
            }
        }

        $subscription = new Subscription();

        $intervalType = $product ? $product->getSubscriptionIntervalType() : ConfigService::$intervalTypeMonthly;

        $intervalCount = $product ? $product->getSubscriptionIntervalCount() : 1;

        $subscription->setBrand($purchaser->getBrand())
            ->setType($type)
            ->setUser($purchaser->getUserObject())
            ->setOrder($order)
            ->setProduct(is_null($orderItem) ? null : $orderItem->getProduct())
            ->setIsActive(true)
            ->setStartDate(Carbon::now())
            ->setPaidUntil($nextBillDate)
            ->setTotalPrice($subscriptionPricePerPayment)
            ->setCurrency($payment->getCurrency())
            ->setIntervalType($intervalType)
            ->setIntervalCount($intervalCount)
            ->setTotalCyclesPaid(1)
            ->setTotalCyclesDue($totalCyclesDue)
            ->setPaymentMethod($payment->getPaymentMethod())
            ->setCreatedAt(Carbon::now());

        return $subscription;
    }
}