<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Illuminate\Session\Store;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Entities\DiscountCriteria;
use Railroad\Ecommerce\Services\DiscountCriteriaService;
use Railroad\Ecommerce\Services\DiscountService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class DiscountServiceTest extends EcommerceTestCase
{
    /**
     * @var Store
     */
    protected $session;

    /**
     * @var $productRepository ProductRepository
     */
    protected $productRepository;

    public function setUp()
    {
        parent::setUp();

        $this->session = $this->app->make(Store::class);
        $this->productRepository = $this->app->make(ProductRepository::class);
    }

    public function test_get_applicable_discounts_names()
    {
        $productOne = $this->fakeProduct([
            'active' => 1,
            'is_physical' => true,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20)
        ]);

        $productTwo = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
            'price' => $this->faker->randomFloat(2, 10, 20)
        ]);

        // prouct three not in order
        $productThree = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
            'price' => $this->faker->randomFloat(2, 15, 20)
        ]);

        // applicable discount - no discount criteria product match required
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
            'min' => $this->faker->numberBetween(1, 100),
            'max' => $this->faker->numberBetween(500, 1000),
        ]);

        // not applicable discount - discount criteria product not in order
        $discountTwo = $this->fakeDiscount([
            'updated_at' => null,
            'active' => true,
            'visible' => false,
            'expiration_date' => Carbon::now()->addDays(2)->toDateTimeString(),
            'type' => DiscountService::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10),
        ]);

        $discountCriteriaTwo = $this->fakeDiscountCriteria([
            'discount_id' => $discountTwo['id'],
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'type' => DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE,
            'min' => $this->faker->numberBetween(1, 2),
            'max' => $this->faker->numberBetween(15, 20),
        ]);

        $discountCriteriaProductTwo = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaTwo['id'],
            'product_id' => $productThree['id'],
        ]);

        // not applicable discount - discount expired
        $discountThree = $this->fakeDiscount([
            'product_id' => $productOne['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => false,
            'expiration_date' => Carbon::now()->subDays(2)->toDateTimeString(),
            'type' => DiscountService::PRODUCT_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10),
        ]);

        $discountCriteriaThree = $this->fakeDiscountCriteria([
            'discount_id' => $discountThree['id'],
            'type' => DiscountCriteriaService::DATE_REQUIREMENT_TYPE,
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'min' => Carbon::now()->subDays(9),
            'max' => Carbon::now()->addDays(2),
        ]);

        // not applicable discount - criteria not met
        $discountFour = $this->fakeDiscount([
            'product_id' => $productTwo['id'],
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
            'max' => $this->faker->numberBetween(5, 9),
        ]);

        // applicable discount - discount product match required, without discount criterias
        $discountFive = $this->fakeDiscount([
            'product_id' => $productTwo['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => true,
            'expiration_date' => null,
            'type' => DiscountService::PRODUCT_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(3, 5),
        ]);

        // not applicable discount - product does not match, without discount criterias
        $discountSix = $this->fakeDiscount([
            'product_id' => $productThree['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => true,
            'expiration_date' => null,
            'type' => DiscountService::PRODUCT_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(3, 5),
        ]);

        $productOneQuantity = $this->faker->numberBetween(4, 7);
        $productTwoQuantity = $this->faker->numberBetween(3, 5);

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

        $cart = Cart::fromSession();

        $totalDueInItems = round(
            $productOne['price'] * $productOneQuantity + $productTwo['price'] * $productTwoQuantity,
            2
        );
        $totalDueInShipping = 0;

        $expectedDiscountsIds = [1, 5];

        $discountService = $this->app->make(DiscountService::class);
        $discountsNames = $discountService->getApplicableDiscountsNames($cart, $totalDueInItems, $totalDueInShipping);

        // assert returned discounts count
        $this->assertEquals(
            count($expectedDiscountsIds),
            count($discountsNames)
        );

        // assert returned discounts ids
        foreach ($discountsNames as $discountData) {
            $this->assertTrue(in_array($discountData['id'], $expectedDiscountsIds));
        }
    }

    public function test_get_total_shipping_discounted_no_overwrite()
    {
        $productOne = $this->fakeProduct([
            'active' => 1,
            'is_physical' => true,
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
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'min' => $this->faker->numberBetween(1, 100),
            'max' => $this->faker->numberBetween(500, 1000),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaOne['id'],
            'product_id' => $productOne['id'],
        ]);

        $discountTwo = $this->fakeDiscount([
            'updated_at' => null,
            'active' => true,
            'visible' => false,
            'expiration_date' => Carbon::now()->addDays(2)->toDateTimeString(), // discount not expired
            'type' => DiscountService::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10),
        ]);

        $discountCriteriaTwo = $this->fakeDiscountCriteria([
            'discount_id' => $discountTwo['id'],
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'type' => DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE,
            'min' => $this->faker->numberBetween(1, 2),
            'max' => $this->faker->numberBetween(15, 20),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaTwo['id'],
            'product_id' => $productTwo['id'],
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
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'min' => Carbon::now()->subDay(1),
            'max' => Carbon::now()->addDays(3),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaThree['id'],
            'product_id' => $productThree['id'],
        ]);

        $discountFour = $this->fakeDiscount([
            'product_id' => $productThree['id'],
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

        $discountFive = $this->fakeDiscount([
            'product_id' => $productOne['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'visible' => true,
            'expiration_date' => Carbon::now()->subDays(2)->toDateTimeString(), // discount expired
            'type' => DiscountService::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(3, 5),
        ]);

        $discountCriteriaFive = $this->fakeDiscountCriteria([
            'discount_id' => $discountOne['id'],
            'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'min' => $this->faker->numberBetween(1, 100),
            'max' => $this->faker->numberBetween(500, 1000),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaFive['id'],
            'product_id' => $productOne['id'],
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
            'is_physical' => true,
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
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'min' => $this->faker->numberBetween(1, 100),
            'max' => $this->faker->numberBetween(500, 1000),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaOne['id'],
            'product_id' => $productOne['id'],
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
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'type' => DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE,
            'min' => $this->faker->numberBetween(1, 2),
            'max' => $this->faker->numberBetween(15, 20),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaTwo['id'],
            'product_id' => $productTwo['id'],
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
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'min' => Carbon::now()->subDay(1),
            'max' => Carbon::now()->addDays(3),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaThree['id'],
            'product_id' => $productThree['id'],
        ]);

        $discountFour = $this->fakeDiscount([
            'product_id' => $productThree['id'],
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
            'is_physical' => true,
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
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'min' => $this->faker->numberBetween(1, 100),
            'max' => $this->faker->numberBetween(500, 1000),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaOne['id'],
            'product_id' => $productOne['id'],
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
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'type' => DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE,
            'min' => $this->faker->numberBetween(1, 2),
            'max' => $this->faker->numberBetween(15, 20),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaTwo['id'],
            'product_id' => $productTwo['id'],
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
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'min' => Carbon::now()->subDay(1),
            'max' => Carbon::now()->addDays(3),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaThree['id'],
            'product_id' => $productThree['id'],
        ]);

        $discountFour = $this->fakeDiscount([
            'product_id' => $productThree['id'],
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
            'is_physical' => true,
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
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'min' => $this->faker->numberBetween(1, 3),
            'max' => $this->faker->numberBetween(15, 20)
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaOne['id'],
            'product_id' => $productOne['id'],
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
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'min' => $this->faker->numberBetween(1, 3),
            'max' => $this->faker->numberBetween(15, 20)
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaThree['id'],
            'product_id' => $productThree['id'],
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
            'stock' => $this->faker->numberBetween(20, 100),
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
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'min' => $this->faker->numberBetween(1, 3),
            'max' => $this->faker->numberBetween(15, 20)
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaOne['id'],
            'product_id' => $productOne['id'],
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
            'min' => Carbon::now()->subDay(5),
            'max' => Carbon::now()->addDays(18),
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
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'min' => $this->faker->numberBetween(1, 3),
            'max' => $this->faker->numberBetween(15, 20)
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaThree['id'],
            'product_id' => $productThree['id'],
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
            'min' => Carbon::now()->subDay(10),
            'max' => Carbon::now()->addDays(12),
        ]);

        $productOneQuantity = $this->faker->numberBetween(5, 7);
        $productTwoQuantity = $this->faker->numberBetween(1, 3);
        $productThreeQuantity = $this->faker->numberBetween(5, 10);
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
            'is_physical' => true,
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
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'min' => $this->faker->numberBetween(1, 100),
            'max' => $this->faker->numberBetween(500, 1000),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaOne['id'],
            'product_id' => $productOne['id'],
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
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'type' => DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE,
            'min' => $this->faker->numberBetween(1, 2),
            'max' => $this->faker->numberBetween(15, 20),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaTwo['id'],
            'product_id' => $productTwo['id'],
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
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'min' => Carbon::now()->subDay(1),
            'max' => Carbon::now()->addDays(3),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaThree['id'],
            'product_id' => $productThree['id'],
        ]);

        $discountFour = $this->fakeDiscount([
            'product_id' => null,
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
            ['id' => $discountOne['id'], 'name' => $discountOne['name']],
            ['id' => $discountTwo['id'], 'name' => $discountTwo['name']],
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
            'is_physical' => true,
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
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'min' => $this->faker->numberBetween(1, 100),
            'max' => $this->faker->numberBetween(500, 1000),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaOne['id'],
            'product_id' => $productOne['id'],
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
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'type' => DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE,
            'min' => $this->faker->numberBetween(1, 2),
            'max' => $this->faker->numberBetween(15, 20),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaTwo['id'],
            'product_id' => $productTwo['id'],
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
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'min' => Carbon::now()->subDay(1),
            'max' => Carbon::now()->addDays(3),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaThree['id'],
            'product_id' => $productThree['id'],
        ]);

        $discountFour = $this->fakeDiscount([
            'product_id' => $productThree['id'],
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
            ['id' => $discountThree['id'], 'name' => $discountThree['name']],
            ['id' => $discountFour['id'], 'name' => $discountFour['name']],
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
            'is_physical' => true,
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
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'min' => $this->faker->numberBetween(1, 30),
            'max' => $this->faker->numberBetween(150, 200),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaOne['id'],
            'product_id' => $product['id'],
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
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'type' => DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE,
            'min' => $this->faker->numberBetween(1, 2),
            'max' => $this->faker->numberBetween(15, 20),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaTwo['id'],
            'product_id' => $product['id'],
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
            $this->productRepository->findProduct($product['id']),
            $totalDueInItems,
            $totalDueInShipping
        );

        $this->assertEquals($expectedCartItemDiscountAmount, $cartItemDiscountAmount);
    }
}
