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
     * @var ShippingService
     */
    private $shippingService;

    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * OrderClaimingService constructor.
     * @param CartService $cartService
     * @param ShippingService $shippingService
     * @param EcommerceEntityManager $entityManager
     */
    public function __construct(
        CartService $cartService,
        ShippingService $shippingService,
        EcommerceEntityManager $entityManager
    )
    {
        $this->cartService = $cartService;
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

        // create the order
        $order = new Order();

        $order->setTotalDue($this->cartService->getDueForOrder())
            ->setProductDue($this->cartService->getTotalItemCosts())
            ->setFinanceDue($this->cartService->getTotalFinanceCosts())
            ->setTaxesDue($this->cartService->getTaxDueForOrder())
            ->setTotalPaid($payment->getTotalPaid())
            ->setBrand($purchaser->getBrand())
            ->setUser($purchaser->getType() == Purchaser::USER_TYPE ? $purchaser->getUserObject() : null)
            ->setCustomer($purchaser->getType() == Purchaser::USER_TYPE ? $purchaser->getCustomerEntity() : null)
            ->setShippingDue(
                $this->shippingService->getShippingDueForCart($cart, $this->cartService->getTotalItemCosts())
            )
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
            $this->entityManager->persist($orderItem);
        }

        // create the order discounts

        // create the payment plan subscription if required

        // create product subscriptions

        // order shipping fulfilment via event

        // save payment to order and subscription if there is one

        // create user product via event

        // product access via event?

        $this->entityManager->flush();
    }
}