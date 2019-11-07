<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\CreditCard;
use Railroad\Ecommerce\Entities\Discount;
use Railroad\Ecommerce\Entities\DiscountCriteria;
use Railroad\Ecommerce\Entities\OrderItem;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\ShippingCostsWeightRange;
use Railroad\Ecommerce\Entities\ShippingOption;
use Railroad\Ecommerce\Entities\Structures\Address as AddressStructure;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\CartItem;
use Railroad\Ecommerce\Entities\Structures\Purchaser;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\CurrencyService;
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

    protected function setUp()
    {
        parent::setUp();

        $this->orderClaimingService = app()->make(OrderClaimingService::class);
    }

    public function test_claim_order()
    {
        $brand = $this->faker->word;
        $country = 'canada';
        $region = 'alberta';
        $currency = 'USD';

        $this->createAndLogInNewUser(); // current user

        $user = $this->fakeUser(['email' => $this->faker->email]); // order placed for

        $quantityOne = $this->faker->numberBetween(2, 4);
        $quantityTwo = 1;
        $discountOneAmount = $this->faker->randomFloat(2, 3, 5);

        $productOnePrice = $this->faker->randomFloat(2, 15, 20);
        $productTwoPrice = $this->faker->randomFloat(2, 15, 20);
        $shippingCost = $this->faker->randomFloat(2, 15, 20);

        $productOne = new Product();

        $productOne->setBrand($brand);
        $productOne->setName($this->faker->word);
        $productOne->setSku('a' . $this->faker->word . rand());
        $productOne->setPrice($productOnePrice);
        $productOne->setCategory($this->faker->word . $this->faker->word);
        $productOne->setType(Product::TYPE_PHYSICAL_ONE_TIME);
        $productOne->setActive(true);
        $productOne->setIsPhysical(true);
        $productOne->setWeight($this->faker->randomFloat(2, 15, 20));
        $productOne->setStock(50);
        $productOne->setCreatedAt(Carbon::now());

        $productTwo = new Product();

        $productTwo->setBrand($brand);
        $productTwo->setName($this->faker->word);
        $productTwo->setSku('b' . $this->faker->word . rand());
        $productTwo->setPrice($productTwoPrice);
        $productTwo->setCategory($this->faker->word . $this->faker->word);
        $productTwo->setType(Product::TYPE_DIGITAL_SUBSCRIPTION);
        $productTwo->setActive(true);
        $productTwo->setIsPhysical(false);
        $productTwo->setWeight(0);
        $productTwo->setSubscriptionIntervalType(config('ecommerce.interval_type_monthly'));
        $productTwo->setSubscriptionIntervalCount($this->faker->numberBetween(0, 12));
        $productTwo->setCreatedAt(Carbon::now());

        $this->entityManager->persist($productOne);
        $this->entityManager->persist($productTwo);

        $this->entityManager->flush();

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
        $discountCriteriaOne->setProductsRelationType(DiscountCriteria::PRODUCTS_RELATION_TYPE_ANY);
        $discountCriteriaOne->setMin(1);
        $discountCriteriaOne->setMax(5);
        $discountCriteriaOne->setDiscount($discountOne);
        $discountCriteriaOne->setCreatedAt(Carbon::now());

        $discountOne->addDiscountCriteria($discountCriteriaOne);

        $shippingCostsWeightRange = new ShippingCostsWeightRange();
        $shippingCostsWeightRange->setMin(1);
        $shippingCostsWeightRange->setMax(1000);
        $shippingCostsWeightRange->setPrice($shippingCost);
        $shippingCostsWeightRange->setCreatedAt(Carbon::now());

        $shippingOption = new ShippingOption();

        $shippingOption->setCountry($country);
        $shippingOption->setActive(true);
        $shippingOption->setPriority(1);
        $shippingOption->addShippingCostsWeightRange($shippingCostsWeightRange);
        $shippingOption->setCreatedAt(Carbon::now());

        $this->entityManager->persist($discountOne);
        $this->entityManager->persist($discountCriteriaOne);
        $this->entityManager->persist($shippingOption);
        $this->entityManager->persist($shippingCostsWeightRange);

        $this->entityManager->flush();

        $purchaser = new Purchaser();

        $purchaser->setId($user['id']);
        $purchaser->setEmail($user['email']);
        $purchaser->setBrand($brand);
        $purchaser->setType(Purchaser::USER_TYPE);

        $billingAddress = new Address();
        $billingAddress->setCountry($country);
        $billingAddress->setRegion($region);
        $billingAddress->setType(Address::BILLING_ADDRESS_TYPE);

        $shippingAddress = new Address();
        $shippingAddress->setCountry($country);
        $shippingAddress->setRegion($region);
        $shippingAddress->setType(Address::SHIPPING_ADDRESS_TYPE);

        $shippingAddressStructure = new AddressStructure();
        $shippingAddressStructure->setCountry($country);
        $shippingAddressStructure->setRegion($region);

        $billingAddressStructure = new AddressStructure();
        $billingAddressStructure->setCountry($country);
        $billingAddressStructure->setRegion($region);

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

        $dueForProductOne = $productOnePrice * $quantityOne - $discountOneAmount * $quantityOne;
        $dueForProductTwo = $productTwoPrice;

        $expectedTotalFromItems = round($dueForProductOne + $dueForProductTwo, 2);

        $expectedTaxRateProduct = config('ecommerce.product_tax_rate')[strtolower($country)][strtolower($region)];
        $expectedTaxRateShipping = config('ecommerce.shipping_tax_rate')[strtolower($country)][strtolower($region)];

        $expectedProductTaxes = round($expectedTaxRateProduct * $expectedTotalFromItems, 2);
        $expectedShippingTaxes = round($expectedTaxRateShipping * $shippingCost, 2);

        $expectedTaxes = round($expectedProductTaxes + $expectedShippingTaxes, 2);

        $expectedOrderTotalDue = round($expectedTotalFromItems + $expectedTaxes + $shippingCost, 2);

        $currencyService = $this->app->make(CurrencyService::class);

        $expectedPaymentTotalDue = $currencyService->convertFromBase(round($expectedOrderTotalDue, 2), $currency);

        $expectedConversionRate = $currencyService->getRate($currency);

        $payment = new Payment();

        $payment->setTotalDue($expectedPaymentTotalDue);
        $payment->setType(Payment::TYPE_INITIAL_ORDER);
        $payment->setStatus(Payment::STATUS_PAID);
        $payment->setCurrency($currency);
        $payment->setTotalPaid($expectedPaymentTotalDue);
        $payment->setPaymentMethod($paymentMethod);
        $payment->setGatewayName($paymentMethod->getMethod()->getPaymentGatewayName());
        $payment->setCreatedAt(Carbon::now());

        $this->entityManager->persist($billingAddress);
        $this->entityManager->persist($creditCard);
        $this->entityManager->persist($paymentMethod);
        $this->entityManager->persist($payment);

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
                'user_id' => $user['id'],
                'customer_id' => null,
                'total_due' => $expectedOrderTotalDue,
                'product_due' => $expectedTotalFromItems,
                'taxes_due' => $expectedTaxes,
                'shipping_due' => $shippingCost,
                'total_paid' => $expectedOrderTotalDue,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_order_items',
            [
                'product_id' => $productOne->getId(),
                'quantity' => $quantityOne,
                'weight' => $productOne->getWeight(),
                'initial_price' => $productOne->getPrice(),
                'total_discounted' => $discountOneAmount * $quantityOne,
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
                'final_price' => $productTwo->getPrice(),
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
                'user_id' => $user['id'],
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
                'user_id' => $user['id'],
                'product_id' => $productOne->getId(),
                'quantity' => $quantityOne,
                'expiration_date' => null,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => $user['id'],
                'product_id' => $productTwo->getId(),
                'quantity' => $quantityTwo,
                'expiration_date' => Carbon::now()
                    ->addMonth($productTwo->getSubscriptionIntervalCount())
                    ->addDays(config('ecommerce.days_before_access_revoked_after_expiry', 5))
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payment_taxes',
            [
                'country' => $country,
                'region' => $region,
                'product_rate' => $expectedTaxRateProduct,
                'shipping_rate' => $expectedTaxRateShipping,
                'product_taxes_paid' => $expectedProductTaxes,
                'shipping_taxes_paid' => $expectedShippingTaxes,
            ]
        );
    }
}
