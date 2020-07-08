<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Doctrine\Common\Collections\ArrayCollection;
use Railroad\Ecommerce\Entities\DiscountCriteria;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\OrderItem;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\CartItem;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\DiscountCriteriaService;
use Railroad\Ecommerce\Services\DiscountService;
use Railroad\Ecommerce\Services\TaxService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class CartServiceTest extends EcommerceTestCase
{
    /**
     * @var TaxService
     */
    public $taxService;

    protected function setUp()
    {
        parent::setUp();

        $cartService = $this->app->make(CartService::class);

        $cartService->clearCart();

        $this->taxService = $this->app->make(TaxService::class);
    }

    public function test_add_to_cart()
    {
        $product = $this->fakeProduct(
            [
                'active' => 1,
                'stock' => $this->faker->numberBetween(20, 100),
                'price' => $this->faker->randomFloat(2, 15, 20)
            ]
        );

        $quantity = $this->faker->numberBetween(1, 10);

        $cartService = $this->app->make(CartService::class);

        $cartService->addToCart(
            $product['sku'],
            $quantity,
            false,
            ''
        );

        $cart = Cart::fromSession();

        $this->assertTrue(is_array($cart->getItems()));

        $this->assertEquals(1, count($cart->getItems()));

        $cartItem = $cart->getItemBySku($product['sku']);

        $this->assertEquals($quantity, $cartItem->getQuantity());
    }

    public function test_remove_from_cart()
    {
        $product = $this->fakeProduct(
            [
                'active' => 1,
                'stock' => $this->faker->numberBetween(20, 100),
                'price' => $this->faker->randomFloat(2, 15, 20)
            ]
        );

        $quantity = $this->faker->numberBetween(1, 10);

        $cart = Cart::fromSession();

        $cart->setItem(new CartItem($product['sku'], $quantity));

        $cart->toSession();

        // remove product
        $cartService = $this->app->make(CartService::class);

        $cartService->removeFromCart($product['sku']);

        $cart = Cart::fromSession();

        $this->assertTrue(is_array($cart->getItems()));

        $this->assertEquals(0, count($cart->getItems()));
    }

    public function test_update_item_quantity_to_cart()
    {
        $product = $this->fakeProduct(
            [
                'active' => 1,
                'stock' => $this->faker->numberBetween(20, 100),
                'price' => $this->faker->randomFloat(2, 15, 20)
            ]
        );

        $initialQuantity = $this->faker->numberBetween(1, 10);

        $cart = Cart::fromSession();

        $cart->setItem(new CartItem($product['sku'], $initialQuantity));

        $cart->toSession();

        $newQuantity = $this->faker->numberBetween(10, 15);

        $cartService = $this->app->make(CartService::class);

        $cartService->updateCartQuantity($product['sku'], $newQuantity);

        $cart = Cart::fromSession();

        $this->assertTrue(is_array($cart->getItems()));

        $this->assertEquals(1, count($cart->getItems()));

        $cartItem = $cart->getItemBySku($product['sku']);

        $this->assertEquals($newQuantity, $cartItem->getQuantity());
    }

    public function test_clear_cart()
    {
        $product = $this->fakeProduct(
            [
                'active' => 1,
                'stock' => $this->faker->numberBetween(20, 100),
                'price' => $this->faker->randomFloat(2, 15, 20)
            ]
        );

        $initialQuantity = $this->faker->numberBetween(1, 10);

        $cart = Cart::fromSession();

        $cart->setItem(new CartItem($product['sku'], $initialQuantity));

        $cart->toSession();

        $newQuantity = $this->faker->numberBetween(10, 15);

        $cartService = $this->app->make(CartService::class);

        $cartService->clearCart();

        $cart = Cart::fromSession();

        $this->assertTrue(is_array($cart->getItems()));

        $this->assertEquals(0, count($cart->getItems()));
    }

    public function test_get_total_item_costs_no_discounts()
    {
        $productOne = $this->fakeProduct(
            [
                'active' => 1,
                'stock' => $this->faker->numberBetween(20, 100),
                'price' => $this->faker->randomFloat(2, 15, 20)
            ]
        );

        $productTwo = $this->fakeProduct(
            [
                'active' => 1,
                'stock' => $this->faker->numberBetween(20, 100),
                'price' => $this->faker->randomFloat(2, 15, 20)
            ]
        );

        $quantityTwo = $this->faker->numberBetween(1, 10);
        $quantityOne = $this->faker->numberBetween(1, 10);

        $expectedTotalItemCosts = round($productOne['price'] * $quantityOne + $productTwo['price'] * $quantityTwo, 2);

        $cart = Cart::fromSession();

        $cart->setItem(new CartItem($productOne['sku'], $quantityOne));
        $cart->setItem(new CartItem($productTwo['sku'], $quantityTwo));

        $cart->toSession();

        $cartService = $this->app->make(CartService::class);

        $cartService->refreshCart();

        $totalItemCosts = $cartService->getTotalItemCosts();

        $this->assertEquals($expectedTotalItemCosts, $totalItemCosts);
    }

    public function test_get_total_item_costs_with_discounts()
    {
        $productOne = $this->fakeProduct(
            [
                'active' => 1,
                'stock' => $this->faker->numberBetween(20, 100),
                'price' => $this->faker->randomFloat(2, 15, 20),
            ]
        );

        $productTwo = $this->fakeProduct(
            [
                'active' => 1,
                'stock' => $this->faker->numberBetween(20, 100),
                'price' => $this->faker->randomFloat(2, 15, 20),
                'category' => $this->faker->word,
            ]
        );

        $productOneQuantity = $this->faker->numberBetween(5, 10);
        $productTwoQuantity = $this->faker->numberBetween(1, 10);

        $discountOne = $this->fakeDiscount(
            [
                'product_id' => $productOne['id'],
                'product_category' => null,
                'updated_at' => null,
                'active' => true,
                'visible' => true,
                'type' => DiscountService::PRODUCT_AMOUNT_OFF_TYPE,
                'amount' => $this->faker->numberBetween(1, 5)
            ]
        );

        $discountCriteriaOne = $this->fakeDiscountCriteria(
            [
                'discount_id' => $discountOne['id'],
                'type' => DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE,
                'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
                'min' => $this->faker->numberBetween(1, 3),
                'max' => $this->faker->numberBetween(15, 20)
            ]
        );

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct(
            [
                'discount_criteria_id' => $discountCriteriaOne['id'],
                'product_id' => $productOne['id'],
            ]
        );

        $cartItemOneDiscountAmount = round($discountOne['amount'] * $productOneQuantity, 2);

        $discountTwo = $this->fakeDiscount(
            [
                'product_id' => null,
                'product_category' => $productTwo['category'],
                'updated_at' => null,
                'active' => true,
                'visible' => false, // name not visible
                'type' => DiscountService::PRODUCT_PERCENT_OFF_TYPE,
                'amount' => $this->faker->numberBetween(1, 10)
            ]
        );

        $discountCriteriaTwo = $this->fakeDiscountCriteria(
            [
                'discount_id' => $discountTwo['id'],
                'type' => DiscountCriteriaService::DATE_REQUIREMENT_TYPE,
                'min' => Carbon::now()->subDay(1),
                'max' => Carbon::now()->addDays(3),
            ]
        );

        $cartItemTwoDiscountAmount =
            round($discountTwo['amount'] * $productTwo['price'] * $productTwoQuantity / 100, 2);

        $totalItemCosts = $productOne['price'] * $productOneQuantity + $productTwo['price'] * $productTwoQuantity;

        $expectedTotalItemCosts = round($totalItemCosts - ($cartItemOneDiscountAmount + $cartItemTwoDiscountAmount), 2);

        $cart = Cart::fromSession();

        $cart->setItem(new CartItem($productOne['sku'], $productOneQuantity));
        $cart->setItem(new CartItem($productTwo['sku'], $productTwoQuantity));

        $cart->toSession();

        $cartService = $this->app->make(CartService::class);

        $cartService->refreshCart();

        $totalItemCosts = $cartService->getTotalItemCosts();

        $this->assertEquals($expectedTotalItemCosts, $totalItemCosts);
    }

    public function test_get_order_item_entities()
    {
        $productOne = $this->fakeProduct(
            [
                'sku' => 'a' . $this->faker->word, // cart items are keyed by sku, this enables the order
                'active' => 1,
                'stock' => $this->faker->numberBetween(20, 100),
                'price' => $this->faker->randomFloat(2, 15, 20),
            ]
        );

        $productTwo = $this->fakeProduct(
            [
                'sku' => 'b' . $this->faker->word, // cart items are keyed by sku, this enables the order
                'active' => 1,
                'stock' => $this->faker->numberBetween(20, 100),
                'price' => $this->faker->randomFloat(2, 15, 20),
                'category' => $this->faker->word,
            ]
        );

        $productOneQuantity = $this->faker->numberBetween(5, 10);
        $productTwoQuantity = $this->faker->numberBetween(1, 10);

        $discountOne = $this->fakeDiscount(
            [
                'product_id' => $productOne['id'],
                'product_category' => null,
                'updated_at' => null,
                'active' => true,
                'visible' => true,
                'type' => DiscountService::PRODUCT_AMOUNT_OFF_TYPE,
                'amount' => $this->faker->numberBetween(1, 5)
            ]
        );

        $discountCriteriaOne = $this->fakeDiscountCriteria(
            [
                'discount_id' => $discountOne['id'],
                'type' => DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE,
                'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
                'min' => $this->faker->numberBetween(1, 3),
                'max' => $this->faker->numberBetween(15, 20)
            ]
        );

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct(
            [
                'discount_criteria_id' => $discountCriteriaOne['id'],
                'product_id' => $productOne['id'],
            ]
        );

        $discountTwo = $this->fakeDiscount(
            [
                'product_id' => null,
                'product_category' => $productTwo['category'],
                'updated_at' => null,
                'active' => true,
                'visible' => false,
                'type' => DiscountService::PRODUCT_PERCENT_OFF_TYPE,
                'amount' => $this->faker->numberBetween(1, 10)
            ]
        );

        $discountCriteriaTwo = $this->fakeDiscountCriteria(
            [
                'discount_id' => $discountTwo['id'],
                'type' => DiscountCriteriaService::DATE_REQUIREMENT_TYPE,
                'min' => Carbon::now()->subDay(1),
                'max' => Carbon::now()->addDays(3),
            ]
        );

        $expectedProductOneDiscountAmount = round($discountOne['amount'] * $productOneQuantity, 2);
        $expectedProductTwoDiscountAmount =
            round($discountTwo['amount'] * $productTwo['price'] * $productTwoQuantity / 100, 2);

        $expectedProductOnePrice =
            round($productOne['price'] * $productOneQuantity - $expectedProductOneDiscountAmount, 2);
        $expectedProductTwoPrice =
            round($productTwo['price'] * $productTwoQuantity - $expectedProductTwoDiscountAmount, 2);

        $cart = Cart::fromSession();

        $cart->setItem(new CartItem($productOne['sku'], $productOneQuantity));
        $cart->setItem(new CartItem($productTwo['sku'], $productTwoQuantity));

        $cart->toSession();

        $cartService = $this->app->make(CartService::class);

        $cartService->refreshCart();

        $result = $cartService->getOrderItemEntities();

        $this->assertTrue(is_array($result));

        $this->assertEquals(2, count($result));

        $orderItemOne = $result[0];

        $this->assertTrue(is_object($orderItemOne));
        $this->assertEquals(OrderItem::class, get_class($orderItemOne));

        // assert order item product
        $this->assertTrue(is_object($orderItemOne->getProduct()));
        $this->assertEquals(Product::class, get_class($orderItemOne->getProduct()));
        $this->assertEquals($productOne['id'], $orderItemOne->getProduct()->getId());

        // assert order item discount
        $this->assertTrue(is_object($orderItemOne->getOrderItemDiscounts()));
        $this->assertEquals(ArrayCollection::class, get_class($orderItemOne->getOrderItemDiscounts()));
        $this->assertEquals(1, count($orderItemOne->getOrderItemDiscounts()));
        $orderItemOneDiscount = $orderItemOne->getOrderItemDiscounts()->first();
        $this->assertEquals($discountOne['id'], $orderItemOneDiscount->getDiscount()->getId());

        // assert order item data
        $this->assertEquals($productOneQuantity, $orderItemOne->getQuantity());
        $this->assertEquals($productOne['weight'], $orderItemOne->getWeight());
        $this->assertEquals($productOne['price'], $orderItemOne->getInitialPrice());
        $this->assertEquals($expectedProductOneDiscountAmount, $orderItemOne->getTotalDiscounted());
        $this->assertEquals($expectedProductOnePrice, $orderItemOne->getFinalPrice());

        $orderItemTwo = $result[1];

        $this->assertTrue(is_object($orderItemTwo));
        $this->assertEquals(OrderItem::class, get_class($orderItemTwo));

        // assert order item product
        $this->assertTrue(is_object($orderItemTwo->getProduct()));
        $this->assertEquals(Product::class, get_class($orderItemTwo->getProduct()));
        $this->assertEquals($productTwo['id'], $orderItemTwo->getProduct()->getId());

        // assert order item discount
        $this->assertTrue(is_object($orderItemTwo->getOrderItemDiscounts()));
        $this->assertEquals(ArrayCollection::class, get_class($orderItemTwo->getOrderItemDiscounts()));
        $this->assertEquals(1, count($orderItemTwo->getOrderItemDiscounts()));
        $orderItemTwoDiscount = $orderItemTwo->getOrderItemDiscounts()->first();
        $this->assertEquals($discountTwo['id'], $orderItemTwoDiscount->getDiscount()->getId());

        // assert order item data
        $this->assertEquals($productTwoQuantity, $orderItemTwo->getQuantity());
        $this->assertEquals($productTwo['weight'], $orderItemTwo->getWeight());
        $this->assertEquals($productTwo['price'], $orderItemTwo->getInitialPrice());
        $this->assertEquals($expectedProductTwoDiscountAmount, $orderItemTwo->getTotalDiscounted());
        $this->assertEquals($expectedProductTwoPrice, $orderItemTwo->getFinalPrice());
    }

    public function test_get_tax_due_for_order()
    {
        $productWeight = $this->faker->randomFloat(2, 5, 10);

        $product = $this->fakeProduct(
            [
                'active' => 1,
                'stock' => $this->faker->numberBetween(20, 100),
                'price' => $this->faker->randomFloat(2, 15, 20),
                'is_physical' => true,
                'weight' => $productWeight
            ]
        );

        $shippingCountry = 'canada';

        $shippingOption = $this->fakeShippingOption(
            [
                'country' => $shippingCountry,
                'active' => true,
                'priority' => 1
            ]
        );

        $shippingCosts = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => 5,
                'max' => 50,
                'price' => $this->faker->randomFloat(2, 3, 5),
            ]
        );

        $quantity = $this->faker->numberBetween(1, 3);

        $shippingRegion = 'quebec';

        $shippingAddress = new Address();
        $shippingAddress->setCountry($shippingCountry);
        $shippingAddress->setRegion($shippingRegion);

        $billingCountry = 'canada';
        $billingRegion =
            $this->faker->randomElement(array_keys(config('ecommerce.tax_rates_and_options')[$billingCountry]));

        $billingAddress = new Address();
        $billingAddress->setCountry($billingCountry);
        $billingAddress->setRegion($billingRegion);

        $expectedItemsCost = $product['price'] * $quantity;
        $expectedShippingCost = $shippingCosts['price'];

        $expectedTaxRateProduct = $this->taxService->getProductTaxRate($shippingAddress);
        $expectedTaxRateShipping = $this->taxService->getShippingTaxRate($shippingAddress);

        $expectedTaxDue =
            round($expectedTaxRateProduct * $expectedItemsCost + $expectedTaxRateShipping * $expectedShippingCost, 2);

        $cart = Cart::fromSession();

        $cart->setShippingAddress($shippingAddress);
        $cart->setBillingAddress($billingAddress);
        $cart->setItem(new CartItem($product['sku'], $quantity));

        $cart->toSession();

        $cartService = $this->app->make(CartService::class);

        $cartService->refreshCart();

        $taxDue = $cartService->getTaxDueForOrder();

        $this->assertEquals($expectedTaxDue, $taxDue);
    }

    public function test_get_due_for_order()
    {
        $productWeight = 5;

        $product = $this->fakeProduct(
            [
                'active' => 1,
                'stock' => 100,
                'is_physical' => true,
                'price' => 15,
                'weight' => $productWeight
            ]
        );

        $shippingCountry = 'canada';

        $shippingOption = $this->fakeShippingOption(
            [
                'country' => $shippingCountry,
                'active' => true,
                'priority' => 1
            ]
        );

        $shippingCosts = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => 2,
                'max' => 50,
                'price' => 3,
            ]
        );

        $quantity = 2;

        $shippingRegion = 'ontario';

        $shippingAddress = new Address();
        $shippingAddress->setCountry($shippingCountry);
        $shippingAddress->setRegion($shippingRegion);


        $billingCountry = 'canada';
        $billingRegion = "new brunswick";

        $billingAddress = new Address();
        $billingAddress->setCountry($billingCountry);
        $billingAddress->setRegion($billingRegion);

        $expectedItemsCost = $product['price'] * $quantity;
        $expectedShippingCost = $shippingCosts['price'];

        $expectedTaxRateProduct = $this->taxService->getProductTaxRate($shippingAddress);
        $expectedTaxRateShipping = $this->taxService->getShippingTaxRate($shippingAddress);

        $expectedTaxDue =
            round($expectedTaxRateProduct * $expectedItemsCost, 2) +
            round($expectedTaxRateShipping * $expectedShippingCost, 2);

        $expectedTotalDue = round($expectedItemsCost + $expectedShippingCost + $expectedTaxDue, 2);

        $cart = Cart::fromSession();

        $cart->setShippingAddress($shippingAddress);
        $cart->setBillingAddress($billingAddress);
        $cart->setItem(new CartItem($product['sku'], $quantity));

        $cart->toSession();

        $cartService = $this->app->make(CartService::class);

        $cartService->refreshCart();

        $dueForOrder = $cartService->getDueForOrder();

        $this->assertEquals($expectedTotalDue, $dueForOrder);
    }

    public function test_get_due_for_initial_payment_bc()
    {
        $productWeight = $this->faker->randomFloat(2, 5, 10);

        $product = $this->fakeProduct(
            [
                'active' => 1,
                'stock' => $this->faker->numberBetween(20, 100),
                'price' => $this->faker->randomFloat(2, 15, 20),
                'weight' => $productWeight,
                'is_physical' => true,
            ]
        );

        $shippingCountry = 'canada';

        $shippingOption = $this->fakeShippingOption(
            [
                'country' => $shippingCountry,
                'active' => true,
                'priority' => 1
            ]
        );

        $shippingCosts = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => 5,
                'max' => 50,
                'price' => $this->faker->randomFloat(2, 3, 5),
            ]
        );

        $quantity = $this->faker->numberBetween(3, 5);

        $shippingRegion = 'british columbia';

        $shippingAddress = new Address();
        $shippingAddress->setCountry($shippingCountry);
        $shippingAddress->setRegion($shippingRegion);

        $billingCountry = 'canada';
        $billingRegion =
            $this->faker->randomElement(array_keys(config('ecommerce.tax_rates_and_options')[$billingCountry]));

        $billingAddress = new Address();
        $billingAddress->setCountry($billingCountry);
        $billingAddress->setRegion($billingRegion);

        $expectedItemsCost = $product['price'] * $quantity;
        $expectedShippingCost = $shippingCosts['price'];

        $expectedTaxRateProduct = $this->taxService->getProductTaxRate($shippingAddress);
        $expectedTaxRateShipping = $this->taxService->getShippingTaxRate($shippingAddress);

        $expectedProductTaxes = round($expectedTaxRateProduct * $expectedItemsCost, 2);
        $expectedShippingTaxes = round($expectedTaxRateShipping * $expectedShippingCost, 2);

        $perPaymentPlanBeforeTax = round(($expectedItemsCost + config('ecommerce.financing_cost_per_order')) / 2, 2);
        $perPaymentPlanAfterTax = round($perPaymentPlanBeforeTax * (1 + $expectedTaxRateProduct), 2);

        $grandTotalDue =
            $expectedItemsCost +
            $expectedShippingCost +
            $expectedProductTaxes +
            $expectedShippingTaxes +
            config('ecommerce.financing_cost_per_order');

//        echo('$expectedItemsCost=' . $expectedItemsCost . "\n");
//        echo('$expectedShippingCost=' . $expectedShippingCost . "\n");
//        echo('$expectedProductTaxes=' . $expectedProductTaxes . "\n");
//        echo('$expectedShippingTaxes=' . $expectedShippingTaxes . "\n");
//        echo('financing_cost_per_order=' . config('ecommerce.financing_cost_per_order') . "\n");

        $numberOfPayments = 2;

        $initialTotalDueBeforeShipping = $perPaymentPlanAfterTax;

        $expectedInitialPayment =
            round($initialTotalDueBeforeShipping + $expectedShippingCost + $expectedShippingTaxes, 2);

        $totalAfterPlanIsComplete = $expectedInitialPayment + ($perPaymentPlanAfterTax *
                (2 - 1));

        if ($grandTotalDue != $totalAfterPlanIsComplete) {
            $expectedInitialPayment += $grandTotalDue - $totalAfterPlanIsComplete;
        }

        $cart = Cart::fromSession();

        $cart->setShippingAddress($shippingAddress);
        $cart->setBillingAddress($billingAddress);
        $cart->setItem(new CartItem($product['sku'], $quantity));
        $cart->setPaymentPlanNumberOfPayments($numberOfPayments);

        $cart->toSession();

        /**
         * @var $cartService CartService
         */
        $cartService = $this->app->make(CartService::class);

        $cartService->refreshCart();

        $dueForInitialPayment = $cartService->getDueForInitialPayment();

        $this->assertEquals($grandTotalDue, $cartService->getDueForOrder());
        $this->assertEquals($expectedInitialPayment, $dueForInitialPayment);

        $this->assertEquals($grandTotalDue, $expectedInitialPayment + $perPaymentPlanAfterTax);
    }

    public function test_get_due_for_payment_plan_payments()
    {
        $product = $this->fakeProduct(
            [
                'active' => 1,
                'stock' => $this->faker->numberBetween(20, 100),
                'price' => $this->faker->randomFloat(2, 15, 20),
                'is_physical' => true,
            ]
        );

        $quantity = $this->faker->numberBetween(1, 3);

        $expectedItemsCost = $product['price'] * $quantity;

        $shippingCountry = 'canada';
        $shippingRegion = 'alberta';

        $shippingAddress = new Address();
        $shippingAddress->setCountry($shippingCountry);
        $shippingAddress->setRegion($shippingRegion);

        $expectedTaxRateProduct = $this->taxService->getProductTaxRate($shippingAddress);

        $expectedTaxDue = $expectedTaxRateProduct * $expectedItemsCost;

        $financeDue = config('ecommerce.financing_cost_per_order');

        $numberOfPayments = 2;

        $expectedDueForPayment = round(
            ($expectedItemsCost + $financeDue) / $numberOfPayments,
            2
        );

        $cart = Cart::fromSession();

        $cart->setShippingAddress($shippingAddress);
        $cart->setItem(new CartItem($product['sku'], $quantity));
        $cart->setPaymentPlanNumberOfPayments($numberOfPayments);

        $cart->toSession();

        $cartService = $this->app->make(CartService::class);

        $cartService->refreshCart();

        $dueForPayment = $cartService->getDueForPaymentPlanPayments();

        $this->assertEquals($expectedDueForPayment, $dueForPayment);
    }

    public function test_populate_order_totals()
    {
        $productWeight = $this->faker->randomFloat(2, 5, 10);

        $product = $this->fakeProduct(
            [
                'active' => 1,
                'stock' => 10,
                'price' => 18.02, // 36.04
                'weight' => 6,
                'type' => Product::TYPE_PHYSICAL_ONE_TIME,
                'is_physical' => true
            ]
        );

        $shippingCountry = 'canada';

        $shippingOption = $this->fakeShippingOption(
            [
                'country' => $shippingCountry,
                'active' => true,
                'priority' => 1
            ]
        );

        $shippingCosts = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => 5,
                'max' => 50,
                'price' => 8.58,
            ]
        );

        // (36.04) + 8.58
        // 44.62 * 0.05

        $quantity = 2;

        $shippingRegion = 'quebec';

        $shippingAddress = new Address();
        $shippingAddress->setCountry($shippingCountry);
        $shippingAddress->setRegion($shippingRegion);

        $billingCountry = 'canada';
        $billingRegion =
            $this->faker->randomElement(array_keys(config('ecommerce.tax_rates_and_options')[$billingCountry]));

        $billingAddress = new Address();
        $billingAddress->setCountry($billingCountry);
        $billingAddress->setRegion($billingRegion);

        $numberOfPayments = 2;

        $expectedItemsCost = $product['price'] * $quantity;
        $expectedShippingCost = $shippingCosts['price'];

        $expectedTaxRateProduct = $this->taxService->getProductTaxRate($shippingAddress);
        $expectedTaxRateShipping = $this->taxService->getShippingTaxRate($shippingAddress);

        $expectedTaxDue =
            (round($expectedTaxRateProduct * $expectedItemsCost, 2) +
                round($expectedTaxRateShipping * $expectedShippingCost, 2));

        $finance = config('ecommerce.financing_cost_per_order');

        $totalToFinance = $expectedItemsCost + $expectedTaxDue + $finance;


        $initialTotalDueBeforeShipping = round($totalToFinance / $numberOfPayments, 2);

        if ($initialTotalDueBeforeShipping * $numberOfPayments != $totalToFinance) {

            $initialTotalDueBeforeShipping += abs($initialTotalDueBeforeShipping * $numberOfPayments - $totalToFinance);
        }

        $expectedInitialPayment = round($initialTotalDueBeforeShipping + $expectedShippingCost, 2);

        $cart = Cart::fromSession();

        $cart->setShippingAddress($shippingAddress);
        $cart->setBillingAddress($billingAddress);
        $cart->setItem(new CartItem($product['sku'], $quantity));
        $cart->setPaymentPlanNumberOfPayments($numberOfPayments);

        $cart->toSession();

        $cartService = $this->app->make(CartService::class);

        $cartService->refreshCart();

        $order = $cartService->populateOrderTotals();

        $this->assertEquals(Order::class, get_class($order));

        $this->assertEquals($expectedItemsCost, $order->getProductDue());
        $this->assertEquals($expectedShippingCost, $order->getShippingDue());
        $this->assertEquals($expectedTaxDue, $order->getTaxesDue());
        $this->assertEquals($finance, $order->getFinanceDue());
    }
}
