<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use PHPUnit\Framework\MockObject\MockObject;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\CreditCard;
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
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\DiscountCriteriaService;
use Railroad\Ecommerce\Services\DiscountService;
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
        $this->cartServiceMock->method('getDueForInitialPayment')
            ->willReturn($dueForOrder);
        $this->shippingServiceMock->method('getShippingDueForCart')
            ->willReturn($shippingDueForOrder);

        $brand = $this->faker->word;
        $country = 'canada';
        $state = 'alberta';
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
        $billingAddress->setCountry($country);
        $billingAddress->setState($state);
        $billingAddress->setType(Address::BILLING_ADDRESS_TYPE);

        $shippingAddress = new Address();
        $shippingAddress->setCountry($country);
        $shippingAddress->setState($state);
        $shippingAddress->setType(Address::SHIPPING_ADDRESS_TYPE);

        $shippingAddressStructure = new AddressStructure();
        $shippingAddressStructure->setCountry($country);
        $shippingAddressStructure->setState($state);

        $billingAddressStructure = new AddressStructure();
        $billingAddress->setCountry($country);
        $billingAddress->setState($state);

        $creditCard = new CreditCard();
        $creditCard->setCardholderName($this->faker->name);
        $creditCard->setCompanyName($this->faker->name);
        $creditCard->setExpirationDate($this->faker->dateTime());
        $creditCard->setExternalCustomerId($this->faker->shuffleString());
        $creditCard->setExternalId($this->faker->shuffleString());
        $creditCard->setFingerprint($this->faker->shuffleString());
        $creditCard->setLastFourDigits(rand(1000, 9999));
        $creditCard->setPaymentGatewayName($this->faker->word);

        $paymentMethod = new PaymentMethod();

        $paymentMethod->setBillingAddress($billingAddress);
        $paymentMethod->setCreditCard($creditCard);
        $paymentMethod->setCurrency($currency);

        $payment = new Payment();

        $payment->setTotalDue($dueForOrder);
        $payment->setType(Payment::TYPE_INITIAL_ORDER);
        $payment->setStatus(Payment::STATUS_PAID);
        $payment->setCurrency($currency);
        $payment->setTotalPaid($dueForOrder);
        $payment->setPaymentMethod($paymentMethod);
        $payment->setGatewayName($paymentMethod->getMethod()->getPaymentGatewayName());
        $payment->setCreatedAt(Carbon::now());

        $this->entityManager->persist($billingAddress);
        $this->entityManager->persist($creditCard);
        $this->entityManager->persist($paymentMethod);
        $this->entityManager->persist($payment);

        $productOne = new Product();

        $productOne->setBrand($brand);
        $productOne->setName($this->faker->word);
        $productOne->setSku($this->faker->word . rand());
        $productOne->setPrice($this->faker->randomFloat(2, 15, 20));
        $productOne->setType(Product::TYPE_PHYSICAL_ONE_TIME);
        $productOne->setActive(true);
        $productOne->setIsPhysical(true);
        $productOne->setWeight($this->faker->randomFloat(2, 15, 20));
        $productOne->setStock(50);
        $productOne->setCreatedAt(Carbon::now());

        $productTwo = new Product();

        $productTwo->setBrand($brand);
        $productTwo->setName($this->faker->word);
        $productTwo->setSku($this->faker->word . rand());
        $productTwo->setPrice($this->faker->randomFloat(2, 15, 20));
        $productTwo->setType(Product::TYPE_DIGITAL_SUBSCRIPTION);
        $productTwo->setActive(true);
        $productTwo->setIsPhysical(false);
        $productTwo->setSubscriptionIntervalType(config('ecommerce.interval_type_monthly'));
        $productTwo->setSubscriptionIntervalCount($this->faker->numberBetween(0, 12));
        $productTwo->setCreatedAt(Carbon::now());

        $discountOne = new Discount();

        $discountOne->setName($this->faker->word);
        $discountOne->setDescription($this->faker->word);
        $discountOne->setType(DiscountService::PRODUCT_AMOUNT_OFF_TYPE);
        $discountOne->setAmount($discountOneAmount);
        $discountOne->setActive(true);
        $discountOne->setVisible(true);
        $discountOne->setProduct($productOne);
        $discountOne->setCreatedAt(Carbon::now());

        $discountCriteriaOne = new DiscountCriteria();

        $discountCriteriaOne->setName($this->faker->word);
        $discountCriteriaOne->setType(DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE);
        $discountCriteriaOne->addProduct($productOne);
        $discountCriteriaOne->setMin(1);
        $discountCriteriaOne->setMax(5);
        $discountCriteriaOne->setDiscount($discountOne);
        $discountCriteriaOne->setCreatedAt(Carbon::now());

        $discountOne->addDiscountCriteria($discountCriteriaOne);

        $this->entityManager->persist($productOne);
        $this->entityManager->persist($productTwo);
        $this->entityManager->persist($discountOne);
        $this->entityManager->persist($discountCriteriaOne);

        $this->entityManager->flush();

        $orderItemOneFinalPrice = round(($productOne->getPrice() - $discountOneAmount) * $quantityOne, 2);

        $orderItemOne = new OrderItem();

        $orderItemOne->setProduct($productOne);
        $orderItemOne->setQuantity($quantityOne);
        $orderItemOne->setInitialPrice($productOne->getPrice());
        $orderItemOne->setTotalDiscounted($discountOneAmount);
        $orderItemOne->setFinalPrice($orderItemOneFinalPrice);
        $orderItemOne->setWeight($productOne->getWeight());

        $orderItemTwo = new OrderItem();

        $orderItemTwo->setProduct($productTwo);
        $orderItemTwo->setQuantity($quantityTwo);
        $orderItemTwo->setInitialPrice($productTwo->getPrice());
        $orderItemTwo->setTotalDiscounted(0);
        $orderItemTwo->setFinalPrice($productTwo->getPrice() * $quantityTwo);

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

        $this->orderClaimingService->claimOrder($purchaser, $payment, $cart, $shippingAddress);

        $this->assertDatabaseHas(
            'ecommerce_orders',
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
            'ecommerce_order_items',
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
            'ecommerce_order_items',
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
            'ecommerce_order_items',
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
            'ecommerce_order_item_fulfillment',
            [
                'order_id' => 1,
                'order_item_id' => 1,
                'status' => config('ecommerce.fulfillment_status_pending'),
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
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
            'ecommerce_subscription_payments',
            [
                'subscription_id' => 1,
                'payment_id' => 1,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => $userId,
                'product_id' => $productOne->getId(),
                'quantity' => $quantityOne,
                'expiration_date' => null,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_user_products',
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
