<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use PHPUnit\Framework\MockObject\MockObject;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\Purchaser;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\OrderClaimingService;
use Railroad\Ecommerce\Services\ShippingService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class OrderClaimingServiceTest extends EcommerceTestCase
{
    /**
     * @var OrderClaimingService
     */
    protected $orderClaimingService;

    /**
     * @var MockObject
     */
    protected $cartServiceMock;

    /**
     * @var MockObject
     */
    protected $shippingServiceMock;

    protected function setUp()
    {
        parent::setUp();

        // mocks
        $this->cartServiceMock =
            $this->getMockBuilder(CartService::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->shippingServiceMock =
            $this->getMockBuilder(ShippingService::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->app->instance(CartService::class, $this->cartServiceMock);
        $this->app->instance(ShippingService::class, $this->shippingServiceMock);

        $this->orderClaimingService = app()->make(OrderClaimingService::class);
    }

    public function test_claim_order()
    {
        $dueForOrder = rand();
        $totalItemCosts = rand();
        $totalFinanceCosts = rand();
        $taxDueForOrder = rand();
        $shippingDueForOrder = rand();

        $this->cartServiceMock->method('getDueForOrder')
            ->willReturn($dueForOrder);
        $this->cartServiceMock->method('getTotalItemCosts')
            ->willReturn($totalItemCosts);
        $this->cartServiceMock->method('getTotalFinanceCosts')
            ->willReturn($totalFinanceCosts);
        $this->cartServiceMock->method('getTaxDueForOrder')
            ->willReturn($taxDueForOrder);
        $this->shippingServiceMock->method('getShippingDueForCart')
            ->willReturn($shippingDueForOrder);

        // todo: populate test entities before passing to service being tested
        $purchaser = new Purchaser();
        $payment = new Payment();
        $cart = new Cart();

        $paymentMethod = new PaymentMethod();
        $billingAddress = new Address();

        $paymentMethod->setBillingAddress($billingAddress);
        $payment->setPaymentMethod($paymentMethod);

        $order = $this->orderClaimingService->claimOrder($purchaser, $payment, $cart);
    }
}
