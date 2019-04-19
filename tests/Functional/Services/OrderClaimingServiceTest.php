<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use PHPUnit\Framework\MockObject\MockObject;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\Discount;
use Railroad\Ecommerce\Entities\DiscountCriteria;
use Railroad\Ecommerce\Entities\OrderItem;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\Address as AddressStructure;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\CartItem;
use Railroad\Ecommerce\Entities\Structures\Purchaser;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\DiscountCriteriaService;
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

        $userId = $this->faker->numberBetween(20, 100);

        $quantityOne = $this->faker->numberBetween(2, 4);
        $quantityTwo = 1;
        $discountOneAmount = $this->faker->randomFloat(2, 3, 5);

        $purchaser = new Purchaser();

        $purchaser->setId($userId);
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
            ->setSubscriptionIntervalType(ConfigService::$intervalTypeMonthly)
            ->setSubscriptionIntervalCount($this->faker->numberBetween(0, 12))
            ->setCreatedAt(Carbon::now());

        $discountOne = new Discount();

        $discountOne
            ->setName($this->faker->word)
            ->setDescription($this->faker->word)
            ->setType(DiscountService::PRODUCT_AMOUNT_OFF_TYPE)
            ->setAmount($discountOneAmount)
            ->setActive(true)
            ->setVisible(true)
            ->setProduct($productOne)
            ->setCreatedAt(Carbon::now());

        $discountCriteriaOne = new DiscountCriteria();

        $discountCriteriaOne
            ->setName($this->faker->word)
            ->setType(DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE)
            ->setProduct($productOne)
            ->setMin(1)
            ->setMax(5)
            ->setDiscount($discountOne)
            ->setCreatedAt(Carbon::now());

        $discountOne->addDiscountCriteria($discountCriteriaOne);

        $this->entityManager->persist($productOne);
        $this->entityManager->persist($productTwo);
        $this->entityManager->persist($discountOne);
        $this->entityManager->persist($discountCriteriaOne);

        $this->entityManager->flush();

        $orderItemOneFinalPrice = round(($productOne->getPrice() - $discountOneAmount) * $quantityOne, 2);

        $orderItemOne = new OrderItem();

        $orderItemOne
            ->setProduct($productOne)
            ->setQuantity($quantityOne)
            ->setInitialPrice($productOne->getPrice())
            ->setTotalDiscounted($discountOneAmount)
            ->setFinalPrice($orderItemOneFinalPrice)
            ->setWeight($productOne->getWeight());

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

        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => $brand,
                'user_id' => $userId,
                'customer_id' => null,
                'total_due' => $dueForOrder,
                'product_due' => $totalItemCosts,
                'taxes_due' => $taxDueForOrder,
                'shipping_due' => $shippingDueForOrder,
                'total_paid' => $dueForOrder,
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableOrderItem,
            [
                'product_id' => $productOne->getId(),
                'quantity' => $quantityOne,
                'weight' => $productOne->getWeight(),
                'initial_price' => $productOne->getPrice(),
                'total_discounted' => $discountOneAmount,
                'final_price' => $orderItemOneFinalPrice,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableOrderItem,
            [
                'product_id' => $productOne->getId(),
                'quantity' => $quantityOne,
                'weight' => $productOne->getWeight(),
                'initial_price' => $productOne->getPrice(),
                'total_discounted' => $discountOneAmount,
                'final_price' => $orderItemOneFinalPrice,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableOrderItem,
            [
                'product_id' => $productTwo->getId(),
                'quantity' => $quantityTwo,
                'weight' => $productTwo->getWeight(),
                'initial_price' => $productTwo->getPrice(),
                'total_discounted' => 0,
                'final_price' => $productTwo->getPrice() * $quantityTwo,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableOrderItemFulfillment,
            [
                'order_id' => 1,
                'order_item_id' => 1,
                'status' => ConfigService::$fulfillmentStatusPending,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableSubscription,
            [
                'brand' => $brand,
                'product_id' => $productTwo->getId(),
                'user_id' => $userId,
                'is_active' => true,
                'start_date' => Carbon::now()
                    ->toDateTimeString(),
                'paid_until' => Carbon::now()
                    ->addMonth($productTwo->getSubscriptionIntervalCount())
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $productOne->getId(),
                'quantity' => $quantityOne,
                'expiration_date' => null,
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $productTwo->getId(),
                'quantity' => $quantityTwo,
                'expiration_date' => Carbon::now()
                    ->addMonth($productTwo->getSubscriptionIntervalCount())
                    ->toDateTimeString(),
            ]
        );
    }
}
