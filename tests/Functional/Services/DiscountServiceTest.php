<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Doctrine\ORM\EntityManager;
use Illuminate\Session\Store;
use Railroad\Ecommerce\Entities\Discount;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\DiscountService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class DiscountServiceTest extends EcommerceTestCase
{
    /**
     * @var Store
     */
    protected $session;

    public function setUp()
    {
        parent::setUp();

        $this->session = $this->app->make(Store::class);
    }

    public function test_apply_discounts()
    {
        $this->session->flush();

        $cartService = $this->app->make(CartService::class);

        // add product discount linked to product
        $productOne = $this->fakeProduct();

        $cartItemOneData = [
            'name' => $this->faker->word,
            'description' => $this->faker->word,
            'quantity' => $this->faker->numberBetween(5, 15),
            'price' => $this->faker->numberBetween(20, 100),
            'requiresShippingAddress' => true,
            'requiresBillingAddress' => true,
            'subscriptionIntervalType' => null,
            'subscriptionIntervalCount' => null,
            'options' => ['product-id' => $productOne['id']]
        ];

        $discountOne = $this->fakeDiscount([
            'product_id' => $productOne['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'type' => DiscountService::PRODUCT_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        // add product discount linked by product category
        $productCategory = $this->faker->word;

        $productTwo = $this->fakeProduct([
            'category' => $productCategory
        ]);

        $cartItemTwoData = [
            'name' => $this->faker->word,
            'description' => $this->faker->word,
            'quantity' => $this->faker->numberBetween(1, 3),
            'price' => $this->faker->numberBetween(20, 100),
            'requiresShippingAddress' => true,
            'requiresBillingAddress' => true,
            'subscriptionIntervalType' => null,
            'subscriptionIntervalCount' => null,
            'options' => ['product-id' => $productTwo['id']]
        ];

        $discountTwo = $this->fakeDiscount([
            'product_id' => null,
            'product_category' => $productCategory,
            'updated_at' => null,
            'active' => true,
            'type' => DiscountService::PRODUCT_PERCENT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        // add order total discount
        $discountThree = $this->fakeDiscount([
            'product_id' => null,
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'type' => DiscountService::ORDER_TOTAL_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        // this discount will be ignored
        $discountFour = $this->fakeDiscount([
            'product_id' => null,
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'type' => $this->faker->word . $this->faker->word,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        $cartService->addCartItem(
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

        $cartService->addCartItem(
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

        $cart = $cartService->getCart();

        $em = $this->app->make(EntityManager::class);

        $discounts = $em
                        ->getRepository(Discount::class)
                        ->findAll();

        // clear discounts added by calling cartService->addCartItem
        foreach ($cart->getItems() as $cartItem) {
            $cartItem->removeAppliedDiscounts();
        }

        $discountService = $this->app->make(DiscountService::class);

        $discountService->applyDiscounts(
            $discounts,
            $cart
        );

        $cartItems = $cartService->getAllCartItems();

        $this->assertTrue(is_array($cartItems));

        $this->assertEquals(2, count($cartItems));

        $cartItemOne = $cartItems[0];

        $this->assertEquals(
            1,
            count($cartItemOne->getAppliedDiscounts())
        );

        $cartItemOneDiscount = $cartItemOne->getAppliedDiscounts()[0];

        $this->assertEquals(
            Discount::class,
            get_class($cartItemOneDiscount)
        );

        $this->assertEquals(
            $discountOne['id'],
            $cartItemOneDiscount->getId()
        );

        $cartItemTwo = $cartItems[1];

        $this->assertEquals(
            1,
            count($cartItemTwo->getAppliedDiscounts())
        );

        $cartItemTwoDiscount = $cartItemTwo->getAppliedDiscounts()[0];

        $this->assertEquals(
            Discount::class,
            get_class($cartItemTwoDiscount)
        );

        $this->assertEquals(
            $discountTwo['id'],
            $cartItemTwoDiscount->getId()
        );

        $cart = $cartService->getCart();

        $cartDiscounts = $cart->getAppliedDiscounts();

        $this->assertEquals(
            1,
            count($cartDiscounts)
        );

        $cartDiscountOne = reset($cartDiscounts);

        $this->assertEquals(
            Discount::class,
            get_class($cartDiscountOne)
        );

        $this->assertEquals(
            $discountThree['id'],
            $cartDiscountOne->getId()
        );
    }

    public function test_get_amount_discounted()
    {
        // add two discounts to pass order total type checks
        $discountOne = $this->fakeDiscount([
            'product_id' => null,
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'type' => DiscountService::ORDER_TOTAL_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        $discountTwo = $this->fakeDiscount([
            'product_id' => null,
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'type' => DiscountService::ORDER_TOTAL_PERCENT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        $discountThree = $this->fakeDiscount([
            'product_id' => null,
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'type' => DiscountService::PRODUCT_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        $discountFour = $this->fakeDiscount([
            'product_id' => null,
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'type' => DiscountService::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        $cartItemsTotalDue = $this->faker->randomFloat(2, 100, 1000);

        $expectedAmountDiscounted = $discountOne['amount'] + ($discountTwo['amount'] / 100 * $cartItemsTotalDue);

        $em = $this->app->make(EntityManager::class);

        $discounts = $em
                        ->getRepository(Discount::class)
                        ->findAll();

        $discountService = $this->app->make(DiscountService::class);

        $amountDiscounted = $discountService->getAmountDiscounted(
            $discounts,
            $cartItemsTotalDue
        );

        $this->assertEquals(
            $expectedAmountDiscounted,
            $amountDiscounted
        );
    }
}
