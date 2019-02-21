<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Doctrine\ORM\EntityManager;
use Illuminate\Session\Store;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\DiscountService;

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

        $cart = $cartService->getCart();

        $em = $this->app->make(EntityManager::class);

        $discounts = $em
                        ->getRepository(Discount::class)
                        ->findAll();


        $discountService = $this->app->make(DiscountService::class);

        $discountService->applyDiscounts(
            $discounts,
            $cart
        );

        // todo - assert first two discounts set on cart items
        // todo - assert discountThree set on cart
    }

    public function test_get_amount_discounted()
    {
        // add two discounts to pass order total type checks
        // add other discounts
        // assert the total return is as expected
    }
}
