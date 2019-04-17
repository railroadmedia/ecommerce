<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Doctrine\Common\Collections\ArrayCollection;
use Railroad\Ecommerce\Entities\OrderDiscount;
use Railroad\Ecommerce\Entities\OrderItem;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\CartItem;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\DiscountCriteriaService;
use Railroad\Ecommerce\Services\DiscountService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class CartServiceTest extends EcommerceTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function test_add_to_cart()
    {
        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20)
        ]);

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
        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20)
        ]);

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
        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20)
        ]);

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
        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20)
        ]);

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
        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20)
        ]);

        $productTwo = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20)
        ]);

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
        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20),
        ]);

        $productTwo = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20),
            'category' => $this->faker->word,
        ]);

        $productOneQuantity = $this->faker->numberBetween(5, 10);
        $productTwoQuantity = $this->faker->numberBetween(1, 10);

        $discountOne = $this->fakeDiscount([
            'product_id' => $productOne['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => true,
            'type' => DiscountService::PRODUCT_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 5)
        ]);

        $discountCriteriaOne = $this->fakeDiscountCriteria([
            'discount_id' => $discountOne['id'],
            'type' => DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE,
            'product_id' => $productOne['id'],
            'min' => $this->faker->numberBetween(1, 3),
            'max' => $this->faker->numberBetween(15, 20)
        ]);

        $cartItemOneDiscountAmount = round($discountOne['amount'] * $productOneQuantity, 2);

        $discountTwo = $this->fakeDiscount([
            'product_id' => null,
            'product_category' => $productTwo['category'],
            'updated_at' => null,
            'active' => true,
            'visible' => false, // name not visible
            'type' => DiscountService::PRODUCT_PERCENT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        $discountCriteriaTwo = $this->fakeDiscountCriteria([
            'discount_id' => $discountTwo['id'],
            'type' => DiscountCriteriaService::DATE_REQUIREMENT_TYPE,
            'min' => Carbon::now()->subDay(1),
            'max' => Carbon::now()->addDays(3),
        ]);

        $cartItemTwoDiscountAmount = round($discountTwo['amount'] * $productTwo['price'] * $productTwoQuantity / 100, 2);

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
        $productOne = $this->fakeProduct([
            'sku' => 'a' . $this->faker->word, // cart items are keyed by sku, this enables the order
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20),
        ]);

        $productTwo = $this->fakeProduct([
            'sku' => 'b' . $this->faker->word, // cart items are keyed by sku, this enables the order
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20),
            'category' => $this->faker->word,
        ]);

        $productOneQuantity = $this->faker->numberBetween(5, 10);
        $productTwoQuantity = $this->faker->numberBetween(1, 10);

        $discountOne = $this->fakeDiscount([
            'product_id' => $productOne['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => true,
            'type' => DiscountService::PRODUCT_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 5)
        ]);

        $discountCriteriaOne = $this->fakeDiscountCriteria([
            'discount_id' => $discountOne['id'],
            'type' => DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE,
            'product_id' => $productOne['id'],
            'min' => $this->faker->numberBetween(1, 3),
            'max' => $this->faker->numberBetween(15, 20)
        ]);

        $discountTwo = $this->fakeDiscount([
            'product_id' => null,
            'product_category' => $productTwo['category'],
            'updated_at' => null,
            'active' => true,
            'visible' => false,
            'type' => DiscountService::PRODUCT_PERCENT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        $discountCriteriaTwo = $this->fakeDiscountCriteria([
            'discount_id' => $discountTwo['id'],
            'type' => DiscountCriteriaService::DATE_REQUIREMENT_TYPE,
            'min' => Carbon::now()->subDay(1),
            'max' => Carbon::now()->addDays(3),
        ]);

        $expectedProductOneDiscountAmount = round($discountOne['amount'] * $productOneQuantity, 2);
        $expectedProductTwoDiscountAmount = round($discountTwo['amount'] * $productTwo['price'] * $productTwoQuantity / 100, 2);

        $expectedProductOnePrice = round($productOne['price'] * $productOneQuantity - $expectedProductOneDiscountAmount, 2);
        $expectedProductTwoPrice = round($productTwo['price'] * $productTwoQuantity - $expectedProductTwoDiscountAmount, 2);

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

    /*
    public function test_get_total_shipping_due()
    {
        $productWeight = $this->faker->randomFloat(2, 5, 10);

        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20),
            'weight' => $productWeight
        ]);

        $quantity = $this->faker->numberBetween(1, 3);

        $countries = ['Canada', 'Serbia', 'Aruba', 'Greece'];

        $shippingCountry = $this->faker->randomElement($countries);

        // option not active
        $shippingOptionOne = $this->fakeShippingOption([
            'country' => $shippingCountry,
            'active' => false,
            'priority' => 1
        ]);

        $shippingCostsOneOne = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOptionOne['id'],
            'min' => 1,
            'max' => 50,
            'price' => $this->faker->randomFloat(2, 3, 5),
        ]);

        $shippingCostsOneTwo = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOptionOne['id'],
            'min' => 51,
            'max' => 100,
            'price' => $this->faker->randomFloat(2, 3, 5),
        ]);

        // option expected to be selected
        $shippingOptionTwo = $this->fakeShippingOption([
            'country' => $shippingCountry,
            'active' => true,
            'priority' => 1
        ]);

        // shipping option costs less than order weight
        $shippingCostsTwoOne = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOptionTwo['id'],
            'min' => 1,
            'max' => 4,
            'price' => $this->faker->randomFloat(2, 1, 3),
        ]);

        // shipping option costs expected to be selected
        $shippingCostsTwoTwo = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOptionTwo['id'],
            'min' => 5,
            'max' => 50,
            'price' => $this->faker->randomFloat(2, 3, 5),
        ]);

        // shipping option costs greater than order weight
        $shippingCostsTwoThree = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOptionTwo['id'],
            'min' => 51,
            'max' => 100,
            'price' => $this->faker->randomFloat(2, 5, 15),
        ]);

        // option matches, but lower priority
        $shippingOptionTwo = $this->fakeShippingOption([
            'country' => '*',
            'active' => true,
            'priority' => 10
        ]);

        $shippingCostsThree = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOptionTwo['id'],
            'min' => 1,
            'max' => 100,
            'price' => $this->faker->randomFloat(2, 5, 10),
        ]);

        $expectedShippingCost = $shippingCostsTwoTwo['price'];

        $cartService = $this->app->make(CartService::class);

        $cartService->addToCart(
            $product['sku'],
            $quantity,
            false,
            ''
        );

        $shippingAddress = new Address();
        $shippingAddress->setCountry($shippingCountry);

        $cartService->setShippingAddress($shippingAddress);

        $cart = Cart::fromSession();

        $this->assertEquals($expectedShippingCost, $cart->getShippingCost());
    }

    public function test_get_total_item_cost_due()
    {
        // add product discount linked to product
        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20)
        ]);

        // add product discount linked by product category
        $productCategory = $this->faker->word;

        $productTwo = $this->fakeProduct([
            'category' => $productCategory,
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
            'price' => $this->faker->randomFloat(2, 10, 20)
        ]);

        $productOneQuantity = $this->faker->numberBetween(4, 7);
        $productTwoQuantity = $this->faker->numberBetween(1, 3);

        $discountOne = $this->fakeDiscount([
            'product_id' => $productOne['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => true,
            'type' => DiscountService::PRODUCT_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 5)
        ]);

        $discountCriteriaOne = $this->fakeDiscountCriteria([
            'discount_id' => $discountOne['id'],
            'type' => DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE,
            'product_id' => $productOne['id'],
            'min' => $this->faker->numberBetween(1, 3),
            'max' => $this->faker->numberBetween(15, 20)
        ]);

        $expectedCartItemOneDiscountAmount = round($discountOne['amount'] * $productOneQuantity, 2);

        $discountTwo = $this->fakeDiscount([
            'product_id' => null,
            'product_category' => $productCategory,
            'updated_at' => null,
            'active' => true,
            'visible' => false, // name not visible
            'type' => DiscountService::PRODUCT_PERCENT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        $discountCriteriaTwo = $this->fakeDiscountCriteria([
            'discount_id' => $discountTwo['id'],
            'type' => DiscountCriteriaService::DATE_REQUIREMENT_TYPE,
            'min' => Carbon::now()->subDay(1),
            'max' => Carbon::now()->addDays(3),
        ]);

        $expectedCartItemTwoDiscountAmount = round($discountTwo['amount'] * $productTwo['price'] * $productTwoQuantity / 100, 2);

        // add order total discount
        $discountThree = $this->fakeDiscount([
            'product_id' => null,
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => true,
            'type' => DiscountService::ORDER_TOTAL_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        $discountCriteriaThree = $this->fakeDiscountCriteria([
            'discount_id' => $discountThree['id'],
            'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
            'min' => $this->faker->numberBetween(1, 5),
            'max' => $this->faker->numberBetween(5000, 10000)
        ]);

        $expectedOrderDiscountAmount = $discountThree['amount'];

        // initial products cost
        $expectedItemsCost = round($productOne['price'] * $productOneQuantity + $productTwo['price'] * $productTwoQuantity, 2);

        // includes discounts
        $expectedItemsCostDue = round($expectedItemsCost - $expectedCartItemOneDiscountAmount - $expectedCartItemTwoDiscountAmount - $expectedOrderDiscountAmount, 2);

        // this discount will be ignored
        $discountFour = $this->fakeDiscount([
            'product_id' => null,
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => true,
            'type' => $this->faker->word . $this->faker->word,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        $cartService = $this->app->make(CartService::class);

        $cartService->addToCart(
            $productOne['sku'],
            $productOneQuantity,
            false,
            ''
        );

        $cartService->addToCart(
            $productTwo['sku'],
            $productTwoQuantity,
            false,
            ''
        );

        $this->assertEquals($expectedItemsCostDue, $cartService->getTotalItemCosts());
    }

    public function test_get_total_tax_due()
    {
        $productWeight = $this->faker->randomFloat(2, 5, 10);

        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20),
            'weight' => $productWeight
        ]);

        $countries = ['Canada', 'Serbia', 'Aruba', 'Greece'];

        $shippingCountry = $this->faker->randomElement($countries);

        $shippingOption = $this->fakeShippingOption([
            'country' => $shippingCountry,
            'active' => true,
            'priority' => 1
        ]);

        $shippingCosts = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min' => 5,
            'max' => 50,
            'price' => $this->faker->randomFloat(2, 3, 5),
        ]);

        $quantity = $this->faker->numberBetween(1, 3);

        $shippingAddress = new Address();
        $shippingAddress->setCountry($shippingCountry);

        $billingCountry = 'canada';
        $billingState = $this->faker->randomElement(array_keys(ConfigService::$taxRate[$billingCountry]));

        $billingAddress = new Address();
        $billingAddress
            ->setCountry($billingCountry)
            ->setState($billingState);

        $expectedItemsCost = $product['price'] * $quantity;
        $expectedShippingCost = $shippingCosts['price'];

        $exptectedTaxRate = ConfigService::$taxRate[$billingCountry][$billingState];

        $exptectedTaxDue = $exptectedTaxRate * ($expectedItemsCost + $expectedShippingCost);

        $cartService = $this->app->make(CartService::class);

        $cartService->setShippingAddress($shippingAddress);
        $cartService->setBillingAddress($billingAddress);

        $cartService->addToCart(
            $product['sku'],
            $quantity,
            false,
            ''
        );

        $this->assertEquals($exptectedTaxDue, $cartService->getTaxDue());
    }

    public function test_get_total_due()
    {
        $productWeight = $this->faker->randomFloat(2, 5, 10);

        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20),
            'weight' => $productWeight
        ]);

        $countries = ['Canada', 'Serbia', 'Aruba', 'Greece'];

        $shippingCountry = $this->faker->randomElement($countries);

        $shippingOption = $this->fakeShippingOption([
            'country' => $shippingCountry,
            'active' => true,
            'priority' => 1
        ]);

        $shippingCosts = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min' => 5,
            'max' => 50,
            'price' => $this->faker->randomFloat(2, 3, 5),
        ]);

        $quantity = $this->faker->numberBetween(1, 3);

        $shippingAddress = new Address();
        $shippingAddress->setCountry($shippingCountry);

        $billingCountry = 'canada';
        $billingState = $this->faker->randomElement(array_keys(ConfigService::$taxRate[$billingCountry]));

        $billingAddress = new Address();
        $billingAddress
            ->setCountry($billingCountry)
            ->setState($billingState);

        $expectedItemsCost = $product['price'] * $quantity;
        $expectedShippingCost = $shippingCosts['price'];

        $exptectedTaxRate = ConfigService::$taxRate[$billingCountry][$billingState];

        $exptectedTaxDue = $exptectedTaxRate * ($expectedItemsCost + $expectedShippingCost);

        $expectedTotalDue = $expectedItemsCost + $expectedShippingCost + $exptectedTaxDue;

        $cartService = $this->app->make(CartService::class);

        $cartService->setShippingAddress($shippingAddress);
        $cartService->setBillingAddress($billingAddress);

        $cartService->addToCart(
            $product['sku'],
            $quantity,
            false,
            ''
        );

        $this->assertEquals($expectedTotalDue, $cartService->getTotalDue());
    }

    public function test_get_due_for_initial_payment()
    {
        $productWeight = $this->faker->randomFloat(2, 5, 10);

        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20),
            'weight' => $productWeight
        ]);

        $countries = ['Canada', 'Serbia', 'Aruba', 'Greece'];

        $shippingCountry = $this->faker->randomElement($countries);

        $shippingOption = $this->fakeShippingOption([
            'country' => $shippingCountry,
            'active' => true,
            'priority' => 1
        ]);

        $shippingCosts = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min' => 5,
            'max' => 50,
            'price' => $this->faker->randomFloat(2, 3, 5),
        ]);

        $quantity = $this->faker->numberBetween(1, 3);

        $shippingAddress = new Address();
        $shippingAddress->setCountry($shippingCountry);

        $billingCountry = 'canada';
        $billingState = $this->faker->randomElement(array_keys(ConfigService::$taxRate[$billingCountry]));

        $billingAddress = new Address();
        $billingAddress
            ->setCountry($billingCountry)
            ->setState($billingState);

        $expectedItemsCost = $product['price'] * $quantity;
        $expectedShippingCost = $shippingCosts['price'];

        $exptectedTaxRate = ConfigService::$taxRate[$billingCountry][$billingState];

        $exptectedTaxDue = $exptectedTaxRate * ($expectedItemsCost + $expectedShippingCost);

        $totalToFinance = $expectedItemsCost + $exptectedTaxDue + config('ecommerce.financing_cost_per_order');

        $numberOfPayments = 2;

        $initialTotalDueBeforeShipping = round($totalToFinance / $numberOfPayments, 2);

        if ($initialTotalDueBeforeShipping * $numberOfPayments != $totalToFinance) {

            $initialTotalDueBeforeShipping += abs($initialTotalDueBeforeShipping * $numberOfPayments - $totalToFinance);
        }

        $expectedInitialPayment = round($initialTotalDueBeforeShipping + $expectedShippingCost, 2);

        $cartService = $this->app->make(CartService::class);

        $cartService->setShippingAddress($shippingAddress);
        $cartService->setBillingAddress($billingAddress);

        $cart = Cart::fromSession();

        $cart->setPaymentPlanNumberOfPayments($numberOfPayments);

        $cart->toSession();

        $cartService->addToCart(
            $product['sku'],
            $quantity,
            false,
            ''
        );

        $this->assertEquals($expectedInitialPayment, $cartService->getTotalDueForInitialPayment());
    }
    */
}
