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

    /*
    public function test_get_total_shipping_discounted()
    {

    }
    */

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

    /*
    public function test_get_shipping_discounts_for_cart()
    {

    }
    */

    /*
    // deprecated
    public function test_apply_discounts_to_cart()
    {
        $this->session->flush();

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

        $cart = Cart::fromSession();

        // reset order discounts
        $cart->setOrderDiscountAmount(0);
        $cart->setCartDiscountNames([]);

        // assert cart items identification
        $this->assertTrue(is_array($cart->getItems()));

        $this->assertEquals(2, count($cart->getItems()));

        $cartItemOne = $cart->getItemBySku($productOne['sku']);
        $cartItemTwo = $cart->getItemBySku($productTwo['sku']);

        // reset cart items discounts
        $cartItemOne->setDiscountAmount(0);

        $cart->setItem($cartItemOne);

        $cartItemTwo->setDiscountAmount(0);

        $cart->setItem($cartItemTwo);

        // apply discounts
        $this->discountService = $this->app->make(DiscountService::class);

        $this->discountService->applyDiscountsToCart($cart);

        // assert discount data
        $this->assertEquals($expectedCartItemOneDiscountAmount, $cartItemOne->getDiscountAmount());

        $this->assertEquals($expectedCartItemTwoDiscountAmount, $cartItemTwo->getDiscountAmount());

        $this->assertEquals($expectedOrderDiscountAmount, $cart->getOrderDiscountAmount());

        $this->assertEquals(
            [
                $discountOne['name'],
                // applied discount two is not set as visible
                $discountThree['name'],
            ],
            $cart->getCartDiscountNames()
        );
    }

    public function test_get_discounts_for_cart()
    {
        $this->session->flush();

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

        $discountTwo = $this->fakeDiscount([
            'product_id' => null,
            'product_category' => $productCategory,
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

        $cart = Cart::fromSession();

        $this->discountService = $this->app->make(DiscountService::class);

        $expectedDiscountsIds = [
            $discountOne['id'],
            $discountTwo['id'],
            $discountThree['id'],
        ];

        $discounts = $this->discountService->getNonShippingDiscountsForCart($cart);

        // assert discounts count
        $this->assertEquals(3, count($discounts));

        $format = 'Discount id %s was not expected';

        foreach ($discounts as $discount) {
            $this->assertTrue(
                in_array($discount->getId(), $expectedDiscountsIds),
                sprintf($format, $discount->getId())
            );
        }
    }
    */
}
