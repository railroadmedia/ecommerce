<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Illuminate\Session\Store;
use Railroad\Ecommerce\Entities\Discount;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\DiscountCriteriaService;
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

    public function test_get_total_shipping_discounted_no_overwrite()
    {
        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20)
        ]);

        $productTwo = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
            'price' => $this->faker->randomFloat(2, 10, 20)
        ]);

        $productThree = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20)
        ]);

        $discountOne = $this->fakeDiscount([
            'product_id' => $productOne['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => true,
            'type' => DiscountService::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(3, 5),
        ]);

        $discountCriteriaOne = $this->fakeDiscountCriteria([
            'discount_id' => $discountOne['id'],
            'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
            'product_id' => $productOne['id'],
            'min' => $this->faker->numberBetween(1, 100),
            'max' => $this->faker->numberBetween(500, 1000),
        ]);

        $discountTwo = $this->fakeDiscount([
            'updated_at' => null,
            'active' => true,
            'visible' => false,
            'type' => DiscountService::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10),
        ]);

        $discountCriteriaTwo = $this->fakeDiscountCriteria([
            'discount_id' => $discountTwo['id'],
            'product_id' => $productTwo['id'],
            'type' => DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE,
            'min' => $this->faker->numberBetween(1, 2),
            'max' => $this->faker->numberBetween(15, 20),
        ]);

        $discountThree = $this->fakeDiscount([
            'product_id' => $productThree['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => false,
            'type' => DiscountService::PRODUCT_AMOUNT_OFF_TYPE, // not applicable
            'amount' => $this->faker->numberBetween(1, 10),
        ]);

        $discountCriteriaThree = $this->fakeDiscountCriteria([
            'discount_id' => $discountThree['id'],
            'type' => DiscountCriteriaService::DATE_REQUIREMENT_TYPE,
            'product_id' => $productThree['id'],
            'min' => Carbon::now()->subDay(1),
            'max' => Carbon::now()->addDays(3),
        ]);

        $discountFour = $this->fakeDiscount([
            'product_id' => $this->faker->numberBetween(20, 30),
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => false,
            'type' => DiscountService::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE,
            'amount' => 1
        ]);

        $discountCriteriaFour = $this->fakeDiscountCriteria([
            'discount_id' => $discountFour['id'],
            'type' => DiscountCriteriaService::SHIPPING_TOTAL_REQUIREMENT_TYPE,
            'min' => $this->faker->numberBetween(1, 3),
            'max' => $this->faker->numberBetween(5, 9), // not applicable
        ]);

        $productOneQuantity = $this->faker->numberBetween(4, 7);
        $productTwoQuantity = $this->faker->numberBetween(3, 5);
        $productThreeQuantity = $this->faker->numberBetween(4, 10);

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

        $cartService->addToCart(
            $productThree['sku'],
            $productThreeQuantity,
            false,
            ''
        );

        $totalDueInItems = round(
                                $productOne['price'] * $productOneQuantity +
                                    $productTwo['price'] * $productTwoQuantity +
                                    $productThree['price'] * $productThreeQuantity +
                                2
                            );

        $totalDueInShipping = $this->faker->randomFloat(2, 10, 20);

        $expectedShippingDiscountAmount = round($discountOne['amount'] * $totalDueInShipping / 100 + $discountTwo['amount'], 2);

        $cart = Cart::fromSession();

        $discountService = $this->app->make(DiscountService::class);

        $totalShippingDiscounted = $discountService->getTotalShippingDiscounted($cart, $totalDueInItems, $totalDueInShipping);

        $this->assertEquals($expectedShippingDiscountAmount, $totalShippingDiscounted);
    }

    public function test_get_total_shipping_discounted_overwrite()
    {
        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20)
        ]);

        $productTwo = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
            'price' => $this->faker->randomFloat(2, 10, 20)
        ]);

        $productThree = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20)
        ]);

        $discountOne = $this->fakeDiscount([
            'product_id' => $productOne['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => true,
            'type' => DiscountService::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(3, 5),
        ]);

        $discountCriteriaOne = $this->fakeDiscountCriteria([
            'discount_id' => $discountOne['id'],
            'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
            'product_id' => $productOne['id'],
            'min' => $this->faker->numberBetween(1, 100),
            'max' => $this->faker->numberBetween(500, 1000),
        ]);

        $discountTwo = $this->fakeDiscount([
            'updated_at' => null,
            'active' => true,
            'visible' => false,
            'type' => DiscountService::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10),
        ]);

        $discountCriteriaTwo = $this->fakeDiscountCriteria([
            'discount_id' => $discountTwo['id'],
            'product_id' => $productTwo['id'],
            'type' => DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE,
            'min' => $this->faker->numberBetween(1, 2),
            'max' => $this->faker->numberBetween(15, 20),
        ]);

        $discountThree = $this->fakeDiscount([
            'product_id' => $productThree['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => false,
            'type' => DiscountService::PRODUCT_AMOUNT_OFF_TYPE, // not applicable
            'amount' => $this->faker->numberBetween(1, 10),
        ]);

        $discountCriteriaThree = $this->fakeDiscountCriteria([
            'discount_id' => $discountThree['id'],
            'type' => DiscountCriteriaService::DATE_REQUIREMENT_TYPE,
            'product_id' => $productThree['id'],
            'min' => Carbon::now()->subDay(1),
            'max' => Carbon::now()->addDays(3),
        ]);

        $discountFour = $this->fakeDiscount([
            'product_id' => $this->faker->numberBetween(20, 30),
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => false,
            'type' => DiscountService::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE,
            'amount' => 1
        ]);

        $discountCriteriaFour = $this->fakeDiscountCriteria([
            'discount_id' => $discountFour['id'],
            'type' => DiscountCriteriaService::SHIPPING_TOTAL_REQUIREMENT_TYPE,
            'min' => $this->faker->numberBetween(1, 3),
            'max' => $this->faker->numberBetween(50, 100),
        ]);

        $productOneQuantity = $this->faker->numberBetween(4, 7);
        $productTwoQuantity = $this->faker->numberBetween(3, 5);
        $productThreeQuantity = $this->faker->numberBetween(4, 10);

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

        $cartService->addToCart(
            $productThree['sku'],
            $productThreeQuantity,
            false,
            ''
        );

        $totalDueInItems = round(
                                $productOne['price'] * $productOneQuantity +
                                    $productTwo['price'] * $productTwoQuantity +
                                    $productThree['price'] * $productThreeQuantity +
                                2
                            );

        $totalDueInShipping = $this->faker->randomFloat(2, 10, 20);

        $expectedShippingDiscountAmount = $totalDueInShipping - $discountFour['amount'];

        $cart = Cart::fromSession();

        $discountService = $this->app->make(DiscountService::class);

        $totalShippingDiscounted = $discountService->getTotalShippingDiscounted($cart, $totalDueInItems, $totalDueInShipping);

        $this->assertEquals($expectedShippingDiscountAmount, $totalShippingDiscounted);
    }

    public function test_get_shipping_discounts_for_cart()
    {
        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20)
        ]);

        $productTwo = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
            'price' => $this->faker->randomFloat(2, 10, 20)
        ]);

        $productThree = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20)
        ]);

        $discountOne = $this->fakeDiscount([
            'product_id' => $productOne['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => true,
            'type' => DiscountService::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(3, 5),
        ]);

        $discountCriteriaOne = $this->fakeDiscountCriteria([
            'discount_id' => $discountOne['id'],
            'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
            'product_id' => $productOne['id'],
            'min' => $this->faker->numberBetween(1, 100),
            'max' => $this->faker->numberBetween(500, 1000),
        ]);

        $discountTwo = $this->fakeDiscount([
            'updated_at' => null,
            'active' => true,
            'visible' => false,
            'type' => DiscountService::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10),
        ]);

        $discountCriteriaTwo = $this->fakeDiscountCriteria([
            'discount_id' => $discountTwo['id'],
            'product_id' => $productTwo['id'],
            'type' => DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE,
            'min' => $this->faker->numberBetween(1, 2),
            'max' => $this->faker->numberBetween(15, 20),
        ]);

        $discountThree = $this->fakeDiscount([
            'product_id' => $productThree['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => false,
            'type' => DiscountService::PRODUCT_AMOUNT_OFF_TYPE, // not applicable
            'amount' => $this->faker->numberBetween(1, 10),
        ]);

        $discountCriteriaThree = $this->fakeDiscountCriteria([
            'discount_id' => $discountThree['id'],
            'type' => DiscountCriteriaService::DATE_REQUIREMENT_TYPE,
            'product_id' => $productThree['id'],
            'min' => Carbon::now()->subDay(1),
            'max' => Carbon::now()->addDays(3),
        ]);

        $discountFour = $this->fakeDiscount([
            'product_id' => $this->faker->numberBetween(20, 30),
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => false,
            'type' => DiscountService::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE,
            'amount' => 1
        ]);

        $discountCriteriaFour = $this->fakeDiscountCriteria([
            'discount_id' => $discountFour['id'],
            'type' => DiscountCriteriaService::SHIPPING_TOTAL_REQUIREMENT_TYPE,
            'min' => $this->faker->numberBetween(1, 3),
            'max' => $this->faker->numberBetween(5, 9), // not applicable
        ]);

        $productOneQuantity = $this->faker->numberBetween(4, 7);
        $productTwoQuantity = $this->faker->numberBetween(3, 5);
        $productThreeQuantity = $this->faker->numberBetween(4, 10);

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

        $cartService->addToCart(
            $productThree['sku'],
            $productThreeQuantity,
            false,
            ''
        );

        $totalDueInItems = round(
                                $productOne['price'] * $productOneQuantity +
                                    $productTwo['price'] * $productTwoQuantity +
                                    $productThree['price'] * $productThreeQuantity +
                                2
                            );

        $expectedDiscountsIds = [
            $discountOne['id'] => true,
            $discountTwo['id'] => true,
            $discountThree['id'] => true,
        ];

        $totalDueInShipping = $this->faker->randomFloat(2, 10, 20);

        $cart = Cart::fromSession();

        $discountService = $this->app->make(DiscountService::class);

        $discounts = $discountService->getShippingDiscountsForCart($cart, $totalDueInItems, $totalDueInShipping);

        $this->assertEquals(2, count($discounts));

        foreach ($discounts as $discount) {
            $this->assertTrue(isset($expectedDiscountsIds[$discount->getId()]));
        }
    }

    public function test_get_total_item_discounted()
    {
        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20)
        ]);

        $productCategory = $this->faker->word;

        $productTwo = $this->fakeProduct([
            'category' => $productCategory,
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
            'price' => $this->faker->randomFloat(2, 10, 20)
        ]);

        $productThree = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20)
        ]);

        $productFour = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20)
        ]);

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
            'product_category' => $productCategory,
            'updated_at' => null,
            'active' => true,
            'visible' => false,
            'type' => DiscountService::ORDER_TOTAL_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        $discountCriteriaTwo = $this->fakeDiscountCriteria([
            'discount_id' => $discountTwo['id'],
            'type' => DiscountCriteriaService::DATE_REQUIREMENT_TYPE,
            'min' => Carbon::now()->subDay(1),
            'max' => Carbon::now()->addDays(3),
        ]);

        $discountThree = $this->fakeDiscount([
            'product_id' => $productThree['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => false,
            'type' => DiscountService::ORDER_TOTAL_PERCENT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        $discountCriteriaThree = $this->fakeDiscountCriteria([
            'discount_id' => $discountThree['id'],
            'type' => DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE,
            'product_id' => $productThree['id'],
            'min' => $this->faker->numberBetween(1, 3),
            'max' => $this->faker->numberBetween(15, 20)
        ]);

        $discountFour = $this->fakeDiscount([
            'product_id' => $productFour['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => false,
            'type' => DiscountService::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        $discountCriteriaFour = $this->fakeDiscountCriteria([
            'discount_id' => $discountFour['id'],
            'type' => DiscountCriteriaService::DATE_REQUIREMENT_TYPE,
            'min' => Carbon::now()->subDay(1),
            'max' => Carbon::now()->addDays(3),
        ]);

        $productOneQuantity = $this->faker->numberBetween(4, 7);
        $productTwoQuantity = $this->faker->numberBetween(1, 3);
        $productThreeQuantity = $this->faker->numberBetween(4, 10);
        $productFourQuantity = $this->faker->numberBetween(1, 3);

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

        $cartService->addToCart(
            $productThree['sku'],
            $productThreeQuantity,
            false,
            ''
        );

        $cartService->addToCart(
            $productFour['sku'],
            $productFourQuantity,
            false,
            ''
        );

        $cart = Cart::fromSession();

        $totalDueInItems = round(
                                $productOne['price'] * $productOneQuantity +
                                    $productTwo['price'] * $productTwoQuantity +
                                    $productThree['price'] * $productThreeQuantity +
                                    $productFour['price'] * $productFourQuantity,
                                2
                            );

        $totalExpectedDiscount = round(
                                    $discountOne['amount'] * $productOneQuantity +
                                        $discountTwo['amount'] +
                                        ($discountThree['amount'] * $totalDueInItems / 100),
                                    2
                                );

        $totalDueInShipping = $this->faker->randomFloat(2, 10, 20);

        $discountService = $this->app->make(DiscountService::class);

        $totalItemDiscounted = $discountService->getTotalItemDiscounted($cart, $totalDueInItems, $totalDueInShipping);

        $this->assertEquals($totalExpectedDiscount, $totalItemDiscounted);
    }

    public function test_get_non_shipping_discounts_for_cart()
    {
        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20)
        ]);

        $productCategory = $this->faker->word;

        $productTwo = $this->fakeProduct([
            'category' => $productCategory,
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
            'price' => $this->faker->randomFloat(2, 10, 20)
        ]);

        $productThree = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20)
        ]);

        $productFour = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20)
        ]);

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
            'product_category' => $productCategory,
            'updated_at' => null,
            'active' => true,
            'visible' => false,
            'type' => DiscountService::ORDER_TOTAL_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        $discountCriteriaTwo = $this->fakeDiscountCriteria([
            'discount_id' => $discountTwo['id'],
            'type' => DiscountCriteriaService::DATE_REQUIREMENT_TYPE,
            'min' => Carbon::now()->subDay(1),
            'max' => Carbon::now()->addDays(3),
        ]);

        $discountThree = $this->fakeDiscount([
            'product_id' => $productThree['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => false,
            'type' => DiscountService::ORDER_TOTAL_PERCENT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        $discountCriteriaThree = $this->fakeDiscountCriteria([
            'discount_id' => $discountThree['id'],
            'type' => DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE,
            'product_id' => $productThree['id'],
            'min' => $this->faker->numberBetween(1, 3),
            'max' => $this->faker->numberBetween(15, 20)
        ]);

        $discountFour = $this->fakeDiscount([
            'product_id' => $productFour['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => false,
            'type' => DiscountService::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        $discountCriteriaFour = $this->fakeDiscountCriteria([
            'discount_id' => $discountFour['id'],
            'type' => DiscountCriteriaService::DATE_REQUIREMENT_TYPE,
            'min' => Carbon::now()->subDay(1),
            'max' => Carbon::now()->addDays(3),
        ]);

        $productOneQuantity = $this->faker->numberBetween(4, 7);
        $productTwoQuantity = $this->faker->numberBetween(1, 3);
        $productThreeQuantity = $this->faker->numberBetween(4, 10);
        $productFourQuantity = $this->faker->numberBetween(1, 3);

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

        $cartService->addToCart(
            $productThree['sku'],
            $productThreeQuantity,
            false,
            ''
        );

        $cartService->addToCart(
            $productFour['sku'],
            $productFourQuantity,
            false,
            ''
        );

        $cart = Cart::fromSession();

        $totalDueInItems = round(
                                $productOne['price'] * $productOneQuantity +
                                    $productTwo['price'] * $productTwoQuantity +
                                    $productThree['price'] * $productThreeQuantity +
                                    $productFour['price'] * $productFourQuantity,
                                2
                            );

        $expectedDiscountsIds = [
            $discountOne['id'] => true,
            $discountTwo['id'] => true,
            $discountThree['id'] => true,
        ];

        $totalDueInShipping = $this->faker->randomFloat(2, 10, 20);

        $discountService = $this->app->make(DiscountService::class);

        $discounts = $discountService->getNonShippingDiscountsForCart($cart, $totalDueInItems, $totalDueInShipping);

        $this->assertEquals(3, count($discounts));

        foreach ($discounts as $discount) {
            $this->assertTrue(isset($expectedDiscountsIds[$discount->getId()]));
        }
    }

    public function test_get_applicable_discounts_names_no_shipping_overwrite()
    {
        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20)
        ]);

        $productTwo = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
            'price' => $this->faker->randomFloat(2, 10, 20)
        ]);

        $productThree = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20)
        ]);

        $discountOne = $this->fakeDiscount([
            'product_id' => $productOne['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => true,
            'type' => DiscountService::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(3, 5),
            'name' => $this->faker->word,
        ]);

        $discountCriteriaOne = $this->fakeDiscountCriteria([
            'discount_id' => $discountOne['id'],
            'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
            'product_id' => $productOne['id'],
            'min' => $this->faker->numberBetween(1, 100),
            'max' => $this->faker->numberBetween(500, 1000),
        ]);

        $discountTwo = $this->fakeDiscount([
            'updated_at' => null,
            'active' => true,
            'visible' => true,
            'type' => DiscountService::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10),
            'name' => $this->faker->word,
        ]);

        $discountCriteriaTwo = $this->fakeDiscountCriteria([
            'discount_id' => $discountTwo['id'],
            'product_id' => $productTwo['id'],
            'type' => DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE,
            'min' => $this->faker->numberBetween(1, 2),
            'max' => $this->faker->numberBetween(15, 20),
        ]);

        $discountThree = $this->fakeDiscount([
            'product_id' => $productThree['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => false, // set as not visible
            'type' => DiscountService::PRODUCT_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10),
            'name' => $this->faker->word,
        ]);

        $discountCriteriaThree = $this->fakeDiscountCriteria([
            'discount_id' => $discountThree['id'],
            'type' => DiscountCriteriaService::DATE_REQUIREMENT_TYPE,
            'product_id' => $productThree['id'],
            'min' => Carbon::now()->subDay(1),
            'max' => Carbon::now()->addDays(3),
        ]);

        $discountFour = $this->fakeDiscount([
            'product_id' => $this->faker->numberBetween(20, 30),
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => false,
            'type' => DiscountService::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE,
            'amount' => 1,
            'name' => $this->faker->word,
        ]);

        $discountCriteriaFour = $this->fakeDiscountCriteria([
            'discount_id' => $discountFour['id'],
            'type' => DiscountCriteriaService::SHIPPING_TOTAL_REQUIREMENT_TYPE,
            'min' => $this->faker->numberBetween(1, 3),
            'max' => $this->faker->numberBetween(5, 9), // not applicable
        ]);

        $productOneQuantity = $this->faker->numberBetween(4, 7);
        $productTwoQuantity = $this->faker->numberBetween(3, 5);
        $productThreeQuantity = $this->faker->numberBetween(4, 10);

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

        $cartService->addToCart(
            $productThree['sku'],
            $productThreeQuantity,
            false,
            ''
        );

        $totalDueInItems = round(
                                $productOne['price'] * $productOneQuantity +
                                    $productTwo['price'] * $productTwoQuantity +
                                    $productThree['price'] * $productThreeQuantity +
                                2
                            );

        $totalDueInShipping = $this->faker->randomFloat(2, 10, 20);

        $expectedDiscountNames = [
            $discountOne['name'],
            $discountTwo['name'],
        ];

        $cart = Cart::fromSession();

        $discountService = $this->app->make(DiscountService::class);

        $discountNames = $discountService->getApplicableDiscountsNames($cart, $totalDueInItems, $totalDueInShipping);

        $this->assertEquals($expectedDiscountNames, $discountNames);
    }

    public function test_get_applicable_discounts_names_shipping_overwrite()
    {
        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20)
        ]);

        $productTwo = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
            'price' => $this->faker->randomFloat(2, 10, 20)
        ]);

        $productThree = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20)
        ]);

        $discountOne = $this->fakeDiscount([
            'product_id' => $productOne['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => true,
            'type' => DiscountService::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(3, 5),
            'name' => $this->faker->word,
        ]);

        $discountCriteriaOne = $this->fakeDiscountCriteria([
            'discount_id' => $discountOne['id'],
            'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
            'product_id' => $productOne['id'],
            'min' => $this->faker->numberBetween(1, 100),
            'max' => $this->faker->numberBetween(500, 1000),
        ]);

        $discountTwo = $this->fakeDiscount([
            'updated_at' => null,
            'active' => true,
            'visible' => false,
            'type' => DiscountService::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10),
            'name' => $this->faker->word,
        ]);

        $discountCriteriaTwo = $this->fakeDiscountCriteria([
            'discount_id' => $discountTwo['id'],
            'product_id' => $productTwo['id'],
            'type' => DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE,
            'min' => $this->faker->numberBetween(1, 2),
            'max' => $this->faker->numberBetween(15, 20),
        ]);

        $discountThree = $this->fakeDiscount([
            'product_id' => $productThree['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => true,
            'type' => DiscountService::PRODUCT_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10),
            'name' => $this->faker->word,
        ]);

        $discountCriteriaThree = $this->fakeDiscountCriteria([
            'discount_id' => $discountThree['id'],
            'type' => DiscountCriteriaService::DATE_REQUIREMENT_TYPE,
            'product_id' => $productThree['id'],
            'min' => Carbon::now()->subDay(1),
            'max' => Carbon::now()->addDays(3),
        ]);

        $discountFour = $this->fakeDiscount([
            'product_id' => $this->faker->numberBetween(20, 30),
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => true,
            'type' => DiscountService::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE,
            'amount' => 1,
            'name' => $this->faker->word,
        ]);

        $discountCriteriaFour = $this->fakeDiscountCriteria([
            'discount_id' => $discountFour['id'],
            'type' => DiscountCriteriaService::SHIPPING_TOTAL_REQUIREMENT_TYPE,
            'min' => $this->faker->numberBetween(1, 3),
            'max' => $this->faker->numberBetween(30, 50),
        ]);

        $productOneQuantity = $this->faker->numberBetween(4, 7);
        $productTwoQuantity = $this->faker->numberBetween(3, 5);
        $productThreeQuantity = $this->faker->numberBetween(4, 10);

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

        $cartService->addToCart(
            $productThree['sku'],
            $productThreeQuantity,
            false,
            ''
        );

        $totalDueInItems = round(
                                $productOne['price'] * $productOneQuantity +
                                    $productTwo['price'] * $productTwoQuantity +
                                    $productThree['price'] * $productThreeQuantity +
                                2
                            );

        $totalDueInShipping = $this->faker->randomFloat(2, 10, 20);

        $expectedDiscountNames = [
            $discountThree['name'],
            $discountFour['name'],
        ];

        $cart = Cart::fromSession();

        $discountService = $this->app->make(DiscountService::class);

        $discountNames = $discountService->getApplicableDiscountsNames($cart, $totalDueInItems, $totalDueInShipping);

        $this->assertEquals($expectedDiscountNames, $discountNames);
    }

    public function test_get_item_discounted_amount()
    {
        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20)
        ]);

        $discountOne = $this->fakeDiscount([
            'product_id' => $product['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => true,
            'type' => DiscountService::PRODUCT_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(3, 5),
            'name' => $this->faker->word,
        ]);

        $discountCriteriaOne = $this->fakeDiscountCriteria([
            'discount_id' => $discountOne['id'],
            'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
            'product_id' => $product['id'],
            'min' => $this->faker->numberBetween(1, 30),
            'max' => $this->faker->numberBetween(150, 200),
        ]);

        $discountTwo = $this->fakeDiscount([
            'product_id' => $product['id'],
            'updated_at' => null,
            'active' => true,
            'visible' => false,
            'type' => DiscountService::PRODUCT_PERCENT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 3),
            'name' => $this->faker->word,
        ]);

        $discountCriteriaTwo = $this->fakeDiscountCriteria([
            'discount_id' => $discountTwo['id'],
            'product_id' => $product['id'],
            'type' => DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE,
            'min' => $this->faker->numberBetween(1, 2),
            'max' => $this->faker->numberBetween(15, 20),
        ]);

        $discountThree = $this->fakeDiscount([
            'product_id' => $this->faker->numberBetween(20, 30),
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => true,
            'type' => DiscountService::ORDER_TOTAL_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10),
            'name' => $this->faker->word,
        ]);

        $discountCriteriaThree = $this->fakeDiscountCriteria([
            'discount_id' => $discountThree['id'],
            'type' => DiscountCriteriaService::DATE_REQUIREMENT_TYPE,
            'min' => Carbon::now()->subDay(1),
            'max' => Carbon::now()->addDays(3),
        ]);

        $discountFour = $this->fakeDiscount([
            'product_id' => $this->faker->numberBetween(20, 30),
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => true,
            'type' => DiscountService::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE,
            'amount' => 1,
            'name' => $this->faker->word,
        ]);

        $discountCriteriaFour = $this->fakeDiscountCriteria([
            'discount_id' => $discountFour['id'],
            'type' => DiscountCriteriaService::SHIPPING_TOTAL_REQUIREMENT_TYPE,
            'min' => $this->faker->numberBetween(1, 3),
            'max' => $this->faker->numberBetween(30, 50),
        ]);

        $productQuantity = $this->faker->numberBetween(3, 5);

        $totalDueInItems = $product['price'] * $productQuantity;
        $totalDueInShipping = $this->faker->randomFloat(2, 10, 20);

        $expectedCartItemDiscountAmount = round($discountOne['amount'] * $productQuantity +
            $discountTwo['amount'] * $productQuantity * $product['price'] / 100, 2);

        $cartService = $this->app->make(CartService::class);

        $cartService->addToCart(
            $product['sku'],
            $productQuantity,
            false,
            ''
        );

        $cart = Cart::fromSession();

        $discountService = $this->app->make(DiscountService::class);

        $cartItemDiscountAmount = $discountService->getItemDiscountedAmount(
            $cart,
            $product['sku'],
            $totalDueInItems,
            $totalDueInShipping
        );

        $this->assertEquals($expectedCartItemDiscountAmount, $cartItemDiscountAmount);
    }
}
