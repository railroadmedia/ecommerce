<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use PHPUnit\Framework\MockObject\MockObject;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\OrderItem;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\Address as AddressStructure;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\CartItem;
use Railroad\Ecommerce\Entities\Structures\Purchaser;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\DiscountService;
use Railroad\Ecommerce\Services\ConfigService;
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
    protected $discountServiceMock;

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

        $this->discountServiceMock =
            $this->getMockBuilder(DiscountService::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->shippingServiceMock =
            $this->getMockBuilder(ShippingService::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->app->instance(CartService::class, $this->cartServiceMock);
        $this->app->instance(DiscountService::class, $this->discountServiceMock);
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

        $brand = $this->faker->word;
        $country = 'canada';
        $state = $this->faker->randomElement(array_keys(ConfigService::$taxRate[$country]));
        $currency = 'USD';

        $quantityOne = $this->faker->numberBetween(1, 3);
        $quantityTwo = $this->faker->numberBetween(1, 3);

        $purchaser = new Purchaser();

        $purchaser->setId($this->faker->numberBetween(20, 100));
        $purchaser->setEmail($this->faker->email);
        $purchaser->setBrand($brand);
        $purchaser->setType(Purchaser::USER_TYPE);

        $billingAddress = new Address();
        $billingAddress
            ->setCountry($country)
            ->setState($state)
            ->setType(ConfigService::$billingAddressType);

        $shippingAddressStructure = new AddressStructure();
        $shippingAddressStructure->setCountry($country)
            ->setState($state);

        $billingAddressStructure = new AddressStructure();
        $billingAddress
            ->setCountry($country)
            ->setState($state);

        $paymentMethod = new PaymentMethod();

        $paymentMethod
            ->setBillingAddress($billingAddress)
            ->setMethodId($this->faker->numberBetween(1, 50))
            ->setMethodType(ConfigService::$creditCartPaymentMethodType)
            ->setCurrency($currency);

        $payment = new Payment();

        $payment
            ->setTotalDue($dueForOrder)
            ->setType(Payment::TYPE_INITIAL_ORDER)
            ->setStatus(Payment::STATUS_PAID)
            ->setCurrency($currency)
            ->setTotalPaid($dueForOrder)
            ->setPaymentMethod($paymentMethod)
            ->setCreatedAt(Carbon::now());

        $this->entityManager->persist($billingAddress);
        $this->entityManager->persist($paymentMethod);
        $this->entityManager->persist($payment);

        $productOne = new Product();

        $productOne
            ->setBrand($brand)
            ->setName($this->faker->word)
            ->setSku($this->faker->word)
            ->setPrice($this->faker->randomFloat(2, 15, 20))
            ->setType(ConfigService::$typeProduct)
            ->setActive(true)
            ->setIsPhysical(true)
            ->setWeight($this->faker->randomFloat(2, 15, 20))
            ->setStock(50)
            ->setCreatedAt(Carbon::now());

        $productTwo = new Product();

        $productTwo
            ->setBrand($brand)
            ->setName($this->faker->word)
            ->setSku($this->faker->word)
            ->setPrice($this->faker->randomFloat(2, 15, 20))
            ->setType(ConfigService::$typeSubscription)
            ->setActive(true)
            ->setIsPhysical(false)
            ->setSubscriptionIntervalType(ConfigService::$intervalTypeDaily)
            ->setSubscriptionIntervalCount($this->faker->numberBetween(0, 12))
            ->setCreatedAt(Carbon::now());

        $this->entityManager->persist($productOne);
        $this->entityManager->persist($productTwo);

        $this->entityManager->flush();

        $orderItemOne = new OrderItem();

        $orderItemOne
            ->setProduct($productOne)
            ->setQuantity($quantityOne)
            ->setInitialPrice($productOne->getPrice())
            ->setTotalDiscounted(0) // todo - update after adding discounts
            ->setFinalPrice($productOne->getPrice() * $quantityOne); // todo - update after adding discounts

        $orderItemTwo = new OrderItem();

        $orderItemTwo
            ->setProduct($productTwo)
            ->setQuantity($quantityTwo)
            ->setInitialPrice($productTwo->getPrice())
            ->setTotalDiscounted(0)
            ->setFinalPrice($productTwo->getPrice() * $quantityTwo);

        $orderItems = [$orderItemOne, $orderItemTwo];

        $this->cartServiceMock->method('getOrderItemEntities')
            ->willReturn($orderItems);

        $this->discountServiceMock->method('getOrderDiscounts')
            ->willReturn([]); // todo - update after adding discounts

        $cart = new Cart();

        $cart->setShippingAddress($shippingAddressStructure);
        $cart->setBillingAddress($billingAddressStructure);
        $cart->setItem(new CartItem($productOne->getSku(), $quantityOne));
        $cart->setItem(new CartItem($productTwo->getSku(), $quantityTwo));
        $cart->setCurrency($currency);

        $cart->toSession();

        $this->orderClaimingService->claimOrder($purchaser, $payment, $cart);

        $this->assertTrue(true); // todo - replace with db asserts
    }
}
