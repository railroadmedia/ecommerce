<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\Discount;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\CartItem;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\DiscountCriteriaService;
use Railroad\Ecommerce\Services\DiscountService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class CartServiceTest extends EcommerceTestCase
{
    /**
     * @var CartService
     */
    protected $classBeingTested;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    protected function setUp()
    {
        parent::setUp();
        $this->classBeingTested = $this->app->make(CartService::class);
    }

    public function test_add_product_to_cart()
    {
        $product = $this->fakeProduct();

        $quantity = $this->faker->numberBetween(1, 10);

        $cart = $this->classBeingTested->addCartItem(
            $this->faker->word,
            $this->faker->word,
            $quantity,
            $product['price'],
            true,
            true,
            null,
            null,
            ['product-id' => $product['id']]
        );

        $cartItem = $cart->getItems()[0];

        $this->assertEquals($cartItem->getQuantity(), $quantity);

        $this->assertEquals(
            $cartItem->getTotalPrice(),
            $quantity * $product['price']
        );
    }

    public function test_update_item_quantity_to_cart()
    {
        $product = $this->fakeProduct();

        $quantityOne = $this->faker->numberBetween(1, 10);
        $quantityTwo = $this->faker->numberBetween(1, 10);

        $this->classBeingTested->addCartItem(
            $this->faker->word,
            $this->faker->word,
            $quantityOne,
            $product['price'],
            true,
            true,
            null,
            null,
            ['product-id' => $product['id']]
        );

        $cart = $this->classBeingTested->addCartItem(
            $this->faker->word,
            $this->faker->word,
            $quantityTwo,
            $product['price'],
            true,
            true,
            null,
            null,
            ['product-id' => $product['id']]
        );

        $cartItem = $cart->getItems()[0];

        $this->assertEquals(
            $cartItem->getQuantity(),
            $quantityOne + $quantityTwo
        );

        $this->assertEquals(
            $cartItem->getTotalPrice(),
            ($quantityOne + $quantityTwo) * $product['price']
        );
    }

    public function test_get_all_cart_items()
    {
        $productOne = $this->fakeProduct();

        $cartItemOneData = [
            'name' => $this->faker->word,
            'description' => $this->faker->word,
            'quantity' => $this->faker->numberBetween(1, 10),
            'price' => $this->faker->numberBetween(10, 100),
            'requiresShippingAddress' => true,
            'requiresBillingAddress' => true,
            'subscriptionIntervalType' => null,
            'subscriptionIntervalCount' => null,
            'options' => ['product-id' => $productOne['id']]
        ];

        $productTwo = $this->fakeProduct();

        $cartItemTwoData = [
            'name' => $this->faker->word,
            'description' => $this->faker->word,
            'quantity' => $this->faker->numberBetween(1, 10),
            'price' => $this->faker->numberBetween(10, 100),
            'requiresShippingAddress' => true,
            'requiresBillingAddress' => true,
            'subscriptionIntervalType' => null,
            'subscriptionIntervalCount' => null,
            'options' => ['product-id' => $productTwo['id']]
        ];

        $this->classBeingTested->addCartItem(
            $cartItemOneData['name'],
            $cartItemOneData['description'],
            $cartItemOneData['quantity'],
            $cartItemOneData['price'],
            $cartItemOneData['requiresShippingAddress'],
            $cartItemOneData['requiresBillingAddress'],
            $cartItemOneData['subscriptionIntervalType'],
            $cartItemOneData['subscriptionIntervalCount'],
            $cartItemOneData['options']
        );

        $this->classBeingTested->addCartItem(
            $cartItemTwoData['name'],
            $cartItemTwoData['description'],
            $cartItemTwoData['quantity'],
            $cartItemTwoData['price'],
            $cartItemTwoData['requiresShippingAddress'],
            $cartItemTwoData['requiresBillingAddress'],
            $cartItemTwoData['subscriptionIntervalType'],
            $cartItemTwoData['subscriptionIntervalCount'],
            $cartItemTwoData['options']
        );

        $cartItems = $this->classBeingTested->getAllCartItems();

        $this->assertTrue(is_array($cartItems));

        $this->assertEquals(2, count($cartItems));

        $cartItemOne = $cartItems[0];

        $this->assertEquals(CartItem::class, get_class($cartItemOne));

        $this->assertEquals(
            $cartItemOneData['name'],
            $cartItemOne->getName()
        );

        $this->assertEquals(
            $cartItemOneData['description'],
            $cartItemOne->getDescription()
        );

        $this->assertEquals(
            $cartItemOneData['quantity'],
            $cartItemOne->getQuantity()
        );

        $this->assertEquals(
            $cartItemOneData['price'],
            $cartItemOne->getPrice()
        );

        $this->assertEquals(
            $cartItemOneData['requiresShippingAddress'],
            $cartItemOne->getRequiresShippingAddress()
        );

        $this->assertEquals(
            $cartItemOneData['requiresBillingAddress'],
            $cartItemOne->getRequiresBillingAddress()
        );

        $this->assertEquals(
            $cartItemOneData['subscriptionIntervalType'],
            $cartItemOne->getSubscriptionIntervalType()
        );

        $this->assertEquals(
            $cartItemOneData['subscriptionIntervalCount'],
            $cartItemOne->getSubscriptionIntervalCount()
        );

        $this->assertEquals(
            $cartItemOneData['options'],
            $cartItemOne->getOptions()
        );
    }

    public function test_remove_all_cart_items()
    {
        $productOne = $this->fakeProduct();

        $cartItemOneData = [
            'name' => $this->faker->word,
            'description' => $this->faker->word,
            'quantity' => $this->faker->numberBetween(1, 10),
            'price' => $this->faker->numberBetween(10, 100),
            'requiresShippingAddress' => true,
            'requiresBillingAddress' => true,
            'subscriptionIntervalType' => null,
            'subscriptionIntervalCount' => null,
            'options' => ['product-id' => $productOne['id']]
        ];

        $productTwo = $this->fakeProduct();

        $cartItemTwoData = [
            'name' => $this->faker->word,
            'description' => $this->faker->word,
            'quantity' => $this->faker->numberBetween(1, 10),
            'price' => $this->faker->numberBetween(10, 100),
            'requiresShippingAddress' => true,
            'requiresBillingAddress' => true,
            'subscriptionIntervalType' => null,
            'subscriptionIntervalCount' => null,
            'options' => ['product-id' => $productTwo['id']]
        ];

        $this->classBeingTested->addCartItem(
            $cartItemOneData['name'],
            $cartItemOneData['description'],
            $cartItemOneData['quantity'],
            $cartItemOneData['price'],
            $cartItemOneData['requiresShippingAddress'],
            $cartItemOneData['requiresBillingAddress'],
            $cartItemOneData['subscriptionIntervalType'],
            $cartItemOneData['subscriptionIntervalCount'],
            $cartItemOneData['options']
        );

        $this->classBeingTested->addCartItem(
            $cartItemTwoData['name'],
            $cartItemTwoData['description'],
            $cartItemTwoData['quantity'],
            $cartItemTwoData['price'],
            $cartItemTwoData['requiresShippingAddress'],
            $cartItemTwoData['requiresBillingAddress'],
            $cartItemTwoData['subscriptionIntervalType'],
            $cartItemTwoData['subscriptionIntervalCount'],
            $cartItemTwoData['options']
        );

        $this->classBeingTested->removeAllCartItems();

        $cartItems = $this->classBeingTested->getAllCartItems();

        $this->assertTrue(is_array($cartItems));

        $this->assertEquals(0, count($cartItems));
    }

    public function test_remove_cart_item()
    {
        $productOne = $this->fakeProduct();

        $cartItemOneData = [
            'name' => $this->faker->word,
            'description' => $this->faker->word,
            'quantity' => $this->faker->numberBetween(1, 10),
            'price' => $this->faker->numberBetween(10, 100),
            'requiresShippingAddress' => true,
            'requiresBillingAddress' => true,
            'subscriptionIntervalType' => null,
            'subscriptionIntervalCount' => null,
            'options' => ['product-id' => $productOne['id']]
        ];

        $productTwo = $this->fakeProduct();

        $cartItemTwoData = [
            'name' => $this->faker->word,
            'description' => $this->faker->word,
            'quantity' => $this->faker->numberBetween(1, 10),
            'price' => $this->faker->numberBetween(10, 100),
            'requiresShippingAddress' => true,
            'requiresBillingAddress' => true,
            'subscriptionIntervalType' => null,
            'subscriptionIntervalCount' => null,
            'options' => ['product-id' => $productTwo['id']]
        ];

        // add two cart items
        $this->classBeingTested->addCartItem(
            $cartItemOneData['name'],
            $cartItemOneData['description'],
            $cartItemOneData['quantity'],
            $cartItemOneData['price'],
            $cartItemOneData['requiresShippingAddress'],
            $cartItemOneData['requiresBillingAddress'],
            $cartItemOneData['subscriptionIntervalType'],
            $cartItemOneData['subscriptionIntervalCount'],
            $cartItemOneData['options']
        );

        $this->classBeingTested->addCartItem(
            $cartItemTwoData['name'],
            $cartItemTwoData['description'],
            $cartItemTwoData['quantity'],
            $cartItemTwoData['price'],
            $cartItemTwoData['requiresShippingAddress'],
            $cartItemTwoData['requiresBillingAddress'],
            $cartItemTwoData['subscriptionIntervalType'],
            $cartItemTwoData['subscriptionIntervalCount'],
            $cartItemTwoData['options']
        );

        // get current cart items
        $cartItems = $this->classBeingTested->getAllCartItems();

        $this->assertTrue(is_array($cartItems));

        $this->assertEquals(2, count($cartItems));

        $cartItemTwo = $cartItems[1];

        // remove item two
        $this->classBeingTested->removeCartItem($cartItemTwo->getId());

        // assert the remaining cart item
        $cartItems = $this->classBeingTested->getAllCartItems();

        $this->assertTrue(is_array($cartItems));

        $this->assertEquals(1, count($cartItems));

        $cartItemOne = $cartItems[0];

        $this->assertEquals(CartItem::class, get_class($cartItemOne));

        $this->assertEquals(
            $cartItemOneData['name'],
            $cartItemOne->getName()
        );

        $this->assertEquals(
            $cartItemOneData['description'],
            $cartItemOne->getDescription()
        );

        $this->assertEquals(
            $cartItemOneData['quantity'],
            $cartItemOne->getQuantity()
        );

        $this->assertEquals(
            $cartItemOneData['price'],
            $cartItemOne->getPrice()
        );

        $this->assertEquals(
            $cartItemOneData['requiresShippingAddress'],
            $cartItemOne->getRequiresShippingAddress()
        );

        $this->assertEquals(
            $cartItemOneData['requiresBillingAddress'],
            $cartItemOne->getRequiresBillingAddress()
        );

        $this->assertEquals(
            $cartItemOneData['subscriptionIntervalType'],
            $cartItemOne->getSubscriptionIntervalType()
        );

        $this->assertEquals(
            $cartItemOneData['subscriptionIntervalCount'],
            $cartItemOne->getSubscriptionIntervalCount()
        );

        $this->assertEquals(
            $cartItemOneData['options'],
            $cartItemOne->getOptions()
        );
    }

    public function test_update_cart_item_quantity()
    {
        $productOne = $this->fakeProduct();
        $productTwo = $this->fakeProduct();

        $initialQuantityOne = $this->faker->numberBetween(1, 10);
        $quantityTwo = $this->faker->numberBetween(1, 10);

        $this->classBeingTested->addCartItem(
            $this->faker->word,
            $this->faker->word,
            $initialQuantityOne,
            $productOne['price'],
            true,
            true,
            null,
            null,
            ['product-id' => $productOne['id']]
        );

        $this->classBeingTested->addCartItem(
            $this->faker->word,
            $this->faker->word,
            $quantityTwo,
            $productTwo['price'],
            true,
            true,
            null,
            null,
            ['product-id' => $productTwo['id']]
        );

        // get current cart items
        $cartItems = $this->classBeingTested->getAllCartItems();

        $this->assertTrue(is_array($cartItems));

        $this->assertEquals(2, count($cartItems));

        $cartItemOne = $cartItems[0];

        $this->assertEquals(
            $cartItemOne->getQuantity(),
            $initialQuantityOne
        );

        // update cart item one quantity
        $newQuantityOne = $this->faker->numberBetween(1, 10);

        $this->classBeingTested->updateCartItemQuantity(
            $cartItemOne->getId(),
            $newQuantityOne
        );

        // get updated cart items
        $cartItems = $this->classBeingTested->getAllCartItems();

        $this->assertTrue(is_array($cartItems));

        $this->assertEquals(2, count($cartItems));

        $cartItemOne = $cartItems[0];

        $this->assertEquals(
            $cartItemOne->getQuantity(),
            $newQuantityOne
        );
    }

    public function test_get_cart_item()
    {
        $productOne = $this->fakeProduct();

        $cartItemOneData = [
            'name' => $this->faker->word,
            'description' => $this->faker->word,
            'quantity' => $this->faker->numberBetween(1, 10),
            'price' => $this->faker->numberBetween(10, 100),
            'requiresShippingAddress' => true,
            'requiresBillingAddress' => true,
            'subscriptionIntervalType' => null,
            'subscriptionIntervalCount' => null,
            'options' => ['product-id' => $productOne['id']]
        ];

        $productTwo = $this->fakeProduct();

        $cartItemTwoData = [
            'name' => $this->faker->word,
            'description' => $this->faker->word,
            'quantity' => $this->faker->numberBetween(1, 10),
            'price' => $this->faker->numberBetween(10, 100),
            'requiresShippingAddress' => true,
            'requiresBillingAddress' => true,
            'subscriptionIntervalType' => null,
            'subscriptionIntervalCount' => null,
            'options' => ['product-id' => $productTwo['id']]
        ];

        $this->classBeingTested->addCartItem(
            $cartItemOneData['name'],
            $cartItemOneData['description'],
            $cartItemOneData['quantity'],
            $cartItemOneData['price'],
            $cartItemOneData['requiresShippingAddress'],
            $cartItemOneData['requiresBillingAddress'],
            $cartItemOneData['subscriptionIntervalType'],
            $cartItemOneData['subscriptionIntervalCount'],
            $cartItemOneData['options']
        );

        $this->classBeingTested->addCartItem(
            $cartItemTwoData['name'],
            $cartItemTwoData['description'],
            $cartItemTwoData['quantity'],
            $cartItemTwoData['price'],
            $cartItemTwoData['requiresShippingAddress'],
            $cartItemTwoData['requiresBillingAddress'],
            $cartItemTwoData['subscriptionIntervalType'],
            $cartItemTwoData['subscriptionIntervalCount'],
            $cartItemTwoData['options']
        );

        $cartItems = $this->classBeingTested->getAllCartItems();

        $this->assertTrue(is_array($cartItems));

        $this->assertEquals(2, count($cartItems));

        $cartItemOne = $cartItems[0];

        $this->assertEquals(CartItem::class, get_class($cartItemOne));

        $idToFetch = $cartItemOne->getId();

        $fetchedCartItem = $this->classBeingTested->getCartItem($idToFetch);

        $this->assertEquals(
            $cartItemOne->getName(),
            $fetchedCartItem->getName()
        );

        $this->assertEquals(
            $cartItemOne->getDescription(),
            $fetchedCartItem->getDescription()
        );

        $this->assertEquals(
            $cartItemOne->getQuantity(),
            $fetchedCartItem->getQuantity()
        );

        $this->assertEquals(
            $cartItemOne->getPrice(),
            $fetchedCartItem->getPrice()
        );

        $this->assertEquals(
            $cartItemOne->getRequiresShippingAddress(),
            $fetchedCartItem->getRequiresShippingAddress()
        );

        $this->assertEquals(
            $cartItemOne->getRequiresBillingAddress(),
            $fetchedCartItem->getRequiresBillingAddress()
        );

        $this->assertEquals(
            $cartItemOne->getSubscriptionIntervalType(),
            $fetchedCartItem->getSubscriptionIntervalType()
        );

        $this->assertEquals(
            $cartItemOne->getSubscriptionIntervalCount(),
            $fetchedCartItem->getSubscriptionIntervalCount()
        );

        $this->assertEquals(
            $cartItemOne->getOptions(),
            $fetchedCartItem->getOptions()
        );
    }

    public function test_get_cart()
    {
        $productOne = $this->fakeProduct();

        $this->classBeingTested->addCartItem(
            $this->faker->word,
            $this->faker->word,
            $this->faker->numberBetween(1, 10),
            $productOne['price'],
            true,
            true,
            null,
            null,
            ['product-id' => $productOne['id']]
        );

        $productTwo = $this->fakeProduct();

        $this->classBeingTested->addCartItem(
            $this->faker->word,
            $this->faker->word,
            $this->faker->numberBetween(1, 10),
            $productTwo['price'],
            true,
            true,
            null,
            null,
            ['product-id' => $productTwo['id']]
        );

        $cart = $this->classBeingTested->getCart();

        $this->assertEquals(Cart::class, get_class($cart));

        $this->assertEquals(
            $cart->getItems(),
            $this->classBeingTested->getAllCartItems()
        );
    }

    public function test_get_discounts_to_apply()
    {
        $productOne = $this->fakeProduct([
            'price' => $this->faker->numberBetween(1, 10)
        ]);

        $discountOne = $this->fakeDiscount([
            'product_id' => $productOne['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true
        ]);

        // current date is between discount criteria min/max
        $discountCriteriaOneMet = $this->fakeDiscountCriteria([
            'product_id' => $productOne['id'],
            'discount_id' => $discountOne['id'],
            'type' => DiscountCriteriaService::DATE_REQUIREMENT_TYPE,
            'min' => Carbon::now()->subDays(2),
            'max' => Carbon::now()->addDays(5),
        ]);

        $this->classBeingTested->addCartItem(
            $this->faker->word,
            $this->faker->word,
            $this->faker->numberBetween(1, 10),
            $productOne['price'],
            true,
            true,
            null,
            null,
            ['product-id' => $productOne['id']]
        );

        $productTwo = $this->fakeProduct([
            'price' => $this->faker->numberBetween(1, 10)
        ]);

        $discountTwo = $this->fakeDiscount([
            'product_id' => $productTwo['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true
        ]);

        $min = 2;
        $max = 5;

        $discountCriteriaTwoMet = $this->fakeDiscountCriteria([
            'product_id' => $productTwo['id'],
            'discount_id' => $discountTwo['id'],
            'type' => DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE,
            'min' => $min,
            'max' => $max,
        ]);

        // added cart item quantity between discount criteria min/max
        $this->classBeingTested->addCartItem(
            $this->faker->word,
            $this->faker->word,
            $this->faker->numberBetween($min, $max),
            $productTwo['price'],
            true,
            true,
            null,
            null,
            ['product-id' => $productTwo['id']]
        );

        $productThree = $this->fakeProduct([
            'price' => $this->faker->numberBetween(1, 10)
        ]);

        $discountThree = $this->fakeDiscount([
            'product_id' => $productThree['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true
        ]);

        $promoCode = $this->faker->word;

        $discountCriteriaThreeNotMet = $this->fakeDiscountCriteria([
            'product_id' => $productThree['id'],
            'discount_id' => $discountThree['id'],
            'type' => DiscountCriteriaService::PROMO_CODE_REQUIREMENT_TYPE,
            'min' => $promoCode,
            'max' => $promoCode,
        ]);

        // no promo code set, discount criteria is not met
        $this->classBeingTested->addCartItem(
            $this->faker->word,
            $this->faker->word,
            $this->faker->numberBetween(1, 10),
            $productThree['price'],
            true,
            true,
            null,
            null,
            ['product-id' => $productThree['id']]
        );

        $productFour = $this->fakeProduct([
            'price' => $this->faker->numberBetween(1, 10)
        ]);

        $discountFour = $this->fakeDiscount([
            'product_id' => $productFour['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => false // inactive
        ]);

        $discountCriteriaFourInactive = $this->fakeDiscountCriteria([
            'product_id' => $productFour['id'],
            'discount_id' => $discountFour['id'],
            'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
            'min' => 2147483647,
            'max' => 2147483647,
        ]);

        $this->classBeingTested->addCartItem(
            $this->faker->word,
            $this->faker->word,
            $this->faker->numberBetween(1, 10),
            $productFour['price'],
            true,
            true,
            null,
            null,
            ['product-id' => $productFour['id']]
        );

        $discountsToApply = $this->classBeingTested->getDiscountsToApply();

        $this->assertTrue(is_array($discountsToApply));

        $this->assertEquals(2, count($discountsToApply));

        $this->assertTrue(isset($discountsToApply[$discountOne['id']]));
        $this->assertTrue(isset($discountsToApply[$discountTwo['id']]));

        $this->assertEquals(
            Discount::class,
            get_class($discountsToApply[$discountOne['id']])
        );
        $this->assertEquals(
            Discount::class,
            get_class($discountsToApply[$discountTwo['id']])
        );
    }

    public function test_apply_discounts()
    {
        $productOne = $this->fakeProduct([
            'price' => $this->faker->numberBetween(100, 1000)
        ]);

        $discountOne = $this->fakeDiscount([
            'product_id' => $productOne['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'type' => DiscountService::PRODUCT_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        // current date is between discount criteria min/max
        $discountCriteriaOneMet = $this->fakeDiscountCriteria([
            'product_id' => $productOne['id'],
            'discount_id' => $discountOne['id'],
            'type' => DiscountCriteriaService::DATE_REQUIREMENT_TYPE,
            'min' => Carbon::now()->subDays(2),
            'max' => Carbon::now()->addDays(5),
        ]);

        $cartItemOneQuantity = $this->faker->numberBetween(1, 10);

        $this->classBeingTested->addCartItem(
            $this->faker->word,
            $this->faker->word,
            $cartItemOneQuantity,
            $productOne['price'],
            true,
            true,
            null,
            null,
            ['product-id' => $productOne['id']]
        );

        $expectedDiscountCartItemOne = $discountOne['amount'] * $cartItemOneQuantity;

        $productTwo = $this->fakeProduct([
            'price' => $this->faker->numberBetween(100, 1000)
        ]);

        $discountTwo = $this->fakeDiscount([
            'product_id' => $productTwo['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'type' => DiscountService::PRODUCT_PERCENT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        $min = 2;
        $max = 5;

        $discountCriteriaTwoMet = $this->fakeDiscountCriteria([
            'product_id' => $productTwo['id'],
            'discount_id' => $discountTwo['id'],
            'type' => DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE,
            'min' => $min,
            'max' => $max,
        ]);

        // added cart item quantity between discount criteria min/max
        $cartItemTwoQuantity = $this->faker->numberBetween($min, $max);

        $this->classBeingTested->addCartItem(
            $this->faker->word,
            $this->faker->word,
            $cartItemTwoQuantity,
            $productTwo['price'],
            true,
            true,
            null,
            null,
            ['product-id' => $productTwo['id']]
        );

        $expectedDiscountCartItemTwo = $discountTwo['amount'] / 100 *
                                $cartItemTwoQuantity * $productTwo['price'];

        $productThree = $this->fakeProduct([
            'price' => $this->faker->numberBetween(1, 10)
        ]);

        $discountThree = $this->fakeDiscount([
            'product_id' => $productThree['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'type' => DiscountService::PRODUCT_PERCENT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        $promoCode = $this->faker->word;

        $discountCriteriaThreeNotMet = $this->fakeDiscountCriteria([
            'product_id' => $productThree['id'],
            'discount_id' => $discountThree['id'],
            'type' => DiscountCriteriaService::PROMO_CODE_REQUIREMENT_TYPE,
            'min' => $promoCode,
            'max' => $promoCode,
        ]);

        // no promo code set, discount criteria is not met
        $this->classBeingTested->addCartItem(
            $this->faker->word,
            $this->faker->word,
            $this->faker->numberBetween(1, 10),
            $productThree['price'],
            true,
            true,
            null,
            null,
            ['product-id' => $productThree['id']]
        );

        $productFour = $this->fakeProduct([
            'price' => $this->faker->numberBetween(1, 10)
        ]);

        $cartItemFourQuantity = $this->faker->numberBetween(1, 10);

        $this->classBeingTested->addCartItem(
            $this->faker->word,
            $this->faker->word,
            $productFour['price'],
            $this->faker->numberBetween(1, 10),
            true,
            true,
            null,
            null,
            ['product-id' => $productFour['id']]
        );

        $productFive = $this->fakeProduct([
            'price' => $this->faker->numberBetween(1, 10)
        ]);

        $discountFour = $this->fakeDiscount([
            'product_id' => $productFive['id'], // this discount is not set for products in cart
            'product_category' => null,
            'updated_at' => null,
            'active' => true
        ]);

        $discountCriteriaFourNotMet = $this->fakeDiscountCriteria([
            'product_id' => $productFive['id'],
            'discount_id' => $discountFour['id'],
            'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
            'min' => 1,
            'max' => 2147483647,
        ]);

        $discountsToApply = $this->classBeingTested->getDiscountsToApply();

        $cart = $this->classBeingTested->getCart();

        $cart->setDiscounts($discountsToApply);

        $cartItems = $this->classBeingTested->getAllCartItems();

        $this->assertTrue(is_array($cartItems));

        $this->assertEquals(4, count($cartItems));

        $this->assertEquals(
            $productOne['id'],
            $cartItems[0]->getProduct()->getId()
        );

        $this->assertEquals(
            $productTwo['id'],
            $cartItems[1]->getProduct()->getId()
        );

        $this->assertEquals(
            $productThree['id'],
            $cartItems[2]->getProduct()->getId()
        );

        $this->assertEquals(
            $productFour['id'],
            $cartItems[3]->getProduct()->getId()
        );

        $this->assertEquals(
            $cartItems[0]->getTotalPrice() - $expectedDiscountCartItemOne,
            $cartItems[0]->getDiscountedPrice()
        );

        $this->assertEquals(
            $cartItems[1]->getTotalPrice() - $expectedDiscountCartItemTwo,
            $cartItems[1]->getDiscountedPrice()
        );

        $this->assertEquals(
            null,
            $cartItems[2]->getDiscountedPrice()
        );

        $this->assertEquals(
            null,
            $cartItems[3]->getDiscountedPrice()
        );

        $this->assertEquals(
            0,
            $cart->getTotalDiscountAmount()
        );
    }

    public function test_apply_discounts_order_total()
    {
        // cart item one has order total discount
        $productOne = $this->fakeProduct([
            'price' => $this->faker->numberBetween(100, 1000)
        ]);

        $discountOne = $this->fakeDiscount([
            'product_id' => $productOne['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'type' => DiscountService::ORDER_TOTAL_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        // current date is between discount criteria min/max
        $discountCriteriaOneMet = $this->fakeDiscountCriteria([
            'product_id' => $productOne['id'],
            'discount_id' => $discountOne['id'],
            'type' => DiscountCriteriaService::DATE_REQUIREMENT_TYPE,
            'min' => Carbon::now()->subDays(2),
            'max' => Carbon::now()->addDays(5),
        ]);

        $cartItemOneQuantity = $this->faker->numberBetween(1, 10);

        $this->classBeingTested->addCartItem(
            $this->faker->word,
            $this->faker->word,
            $cartItemOneQuantity,
            $productOne['price'],
            true,
            true,
            null,
            null,
            ['product-id' => $productOne['id']]
        );

        // cart item two has no discounts
        $productTwo = $this->fakeProduct([
            'price' => $this->faker->numberBetween(1, 10)
        ]);

        $cartItemTwoQuantity = $this->faker->numberBetween(1, 10);

        $this->classBeingTested->addCartItem(
            $this->faker->word,
            $this->faker->word,
            $productTwo['price'],
            $this->faker->numberBetween(1, 10),
            true,
            true,
            null,
            null,
            ['product-id' => $productTwo['id']]
        );

        // this discount is not set for products in cart
        $productThree = $this->fakeProduct([
            'price' => $this->faker->numberBetween(1, 10)
        ]);

        $discountTwo = $this->fakeDiscount([
            'product_id' => $productThree['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true
        ]);

        $discountCriteriaTwoNotmet = $this->fakeDiscountCriteria([
            'product_id' => $productThree['id'],
            'discount_id' => $discountTwo['id'],
            'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
            'min' => 1,
            'max' => 2147483647,
        ]);

        $discountsToApply = $this->classBeingTested->getDiscountsToApply();

        $cart = $this->classBeingTested->getCart();

        $cart->setDiscounts($discountsToApply);

        $this->assertEquals(
            $discountOne['amount'],
            $cart->getTotalDiscountAmount()
        );
    }
}
