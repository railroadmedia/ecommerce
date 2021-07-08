<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Illuminate\Session\Store;
use Railroad\Ecommerce\Entities\DiscountCriteria;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\DiscountCriteriaService;
use Railroad\Ecommerce\Services\DiscountService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class DiscountCriteriaServiceTest extends EcommerceTestCase
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

    public function test_cart_items_total_requirement_met()
    {
        $this->session->flush();

        $userId = $this->createAndLogInNewUser();

        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
        ]);
        $productTwo = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
        ]);

        $discount = $this->fakeDiscount([
            'product_id' => $productOne['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'type' => DiscountService::ORDER_TOTAL_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'type' => DiscountCriteriaService::CART_ITEMS_TOTAL_REQUIREMENT_TYPE,
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'min' => $this->faker->numberBetween(1, 3),
            'max' => $this->faker->numberBetween(15, 20)
        ]);

        $productOneQuantity = $this->faker->numberBetween(3, 5);
        $productTwoQuantity = $this->faker->numberBetween(3, 5);
        // total quantity is between discount criteria min/max

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

        $em = $this->app->make(EcommerceEntityManager::class);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->cartItemsTotalRequirement($discountCriteria, $cart);

        $this->assertTrue($metCriteria);
    }

    public function test_cart_items_total_requirement_met_not_met()
    {
        $this->session->flush();

        $userId = $this->createAndLogInNewUser();

        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(30, 100),
        ]);
        $productTwo = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(30, 100),
        ]);

        $discount = $this->fakeDiscount([
            'product_id' => $productOne['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'type' => DiscountService::ORDER_TOTAL_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'type' => DiscountCriteriaService::CART_ITEMS_TOTAL_REQUIREMENT_TYPE,
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'min' => $this->faker->numberBetween(1, 3),
            'max' => $this->faker->numberBetween(15, 20)
        ]);

        $productOneQuantity = $this->faker->numberBetween(25, 30);
        $productTwoQuantity = $this->faker->numberBetween(25, 30);
        // total quantity is not between discount criteria min/max

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

        $em = $this->app->make(EcommerceEntityManager::class);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->cartItemsTotalRequirement($discountCriteria, $cart);

        $this->assertFalse($metCriteria);
    }

    public function test_discount_criteria_met_for_order_not_met()
    {
        $this->session->flush();

        $userId = $this->createAndLogInNewUser();

        $cart = Cart::fromSession();

        $totalDueInItems = $this->faker->randomFloat(2, 100, 200);
        $totalDueInShipping = $this->faker->randomFloat(2, 10, 20);

        $em = $this->app->make(EcommerceEntityManager::class);

        // all discountCriteriaMetForOrder switch cases are tested below, except default
        $discountCriteriaData = $this->fakeDiscountCriteria([
            'type' => $this->faker->word . $this->faker->word,
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->discountCriteriaMetForOrder($discountCriteria, $cart, $totalDueInItems, $totalDueInShipping);

        $this->assertFalse($metCriteria);
    }

    public function test_product_quantity_requirement_met()
    {
        $this->session->flush();

        $userId = $this->createAndLogInNewUser();

        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
        ]);
        $productTwo = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
        ]);

        $discount = $this->fakeDiscount([
            'product_id' => $productOne['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'type' => DiscountService::ORDER_TOTAL_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'type' => DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE,
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            'min' => $this->faker->numberBetween(1, 5),
            'max' => $this->faker->numberBetween(15, 20)
        ]);

        $productOneQuantity = $this->faker->numberBetween(6, 14); // quantity is between discount criteria min/max
        $productTwoQuantity = $this->faker->numberBetween(1, 5);

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

        $em = $this->app->make(EcommerceEntityManager::class);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->productQuantityRequirementMet($discountCriteria, $cart);

        $this->assertTrue($metCriteria);
    }

    public function test_product_quantity_requirement_met_not_met()
    {
        $this->session->flush();

        $userId = $this->createAndLogInNewUser();

        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
        ]);
        $productTwo = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
        ]);

        $discount = $this->fakeDiscount([
            'product_id' => $productOne['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'type' => DiscountService::ORDER_TOTAL_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'type' => DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE,
            'min' => $this->faker->numberBetween(5, 10),
            'max' => $this->faker->numberBetween(50, 100),
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ANY
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaData['id'],
            'product_id' => $productOne['id'],
        ]);

        $productOneQuantity = $this->faker->numberBetween(1, 4); // quantity is less than discount criteria min/max
        $productTwoQuantity = $this->faker->numberBetween(1, 4); // quantity is less than discount criteria min/max

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

        $em = $this->app->make(EcommerceEntityManager::class);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->productQuantityRequirementMet($discountCriteria, $cart);

        $this->assertFalse($metCriteria);
    }

    public function test_order_date_requirement_met()
    {
        $userId = $this->createAndLogInNewUser();

        $em = $this->app->make(EcommerceEntityManager::class);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'min' => Carbon::now()->subDays(3),
            'max' => Carbon::now()->addDays(5),
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
        ]);

        $product = $this->fakeProduct([
            'price' => $this->faker->randomFloat(2, 60, 100),
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaData['id'],
            'product_id' => $product['id'],
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->orderDateRequirement($discountCriteria);

        $this->assertTrue($metCriteria);
    }

    public function test_order_date_requirement_not_met()
    {
        $this->session->flush();

        $userId = $this->createAndLogInNewUser();

        $em = $this->app->make(EcommerceEntityManager::class);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'min' => Carbon::now()->subDays(5),
            'max' => Carbon::now()->subDays(3),
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
        ]);

        $product = $this->fakeProduct([
            'price' => $this->faker->randomFloat(2, 60, 100),
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaData['id'],
            'product_id' => $product['id'],
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->orderDateRequirement($discountCriteria);

        $this->assertFalse($metCriteria);
    }

    public function test_order_total_requirement_met()
    {
        $this->session->flush();

        $userId = $this->createAndLogInNewUser();

        $productOne = $this->fakeProduct([
            'price' => 12.5,
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
        ]);
        $productTwo = $this->fakeProduct([
            'price' => 12.5,
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
        ]);

        $discount = $this->fakeDiscount([
            'product_id' => $productOne['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'type' => DiscountService::ORDER_TOTAL_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
            'min' => $this->faker->numberBetween(1, 5),
            'max' => $this->faker->numberBetween(500, 1000),
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaData['id'],
            'product_id' => $productOne['id'],
        ]);

        $productOneQuantity = $this->faker->numberBetween(1, 5);
        $productTwoQuantity = $this->faker->numberBetween(1, 5);

        $totalDueInItems = $productOne['price'] * $productOneQuantity + $productTwo['price'] * $productTwoQuantity;

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

        $em = $this->app->make(EcommerceEntityManager::class);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->orderTotalRequirement($discountCriteria, $totalDueInItems);

        $this->assertTrue($metCriteria);
    }

    public function test_order_total_requirement_not_met()
    {
        $this->session->flush();

        $userId = $this->createAndLogInNewUser();

        $productOne = $this->fakeProduct([
            'price' => $this->faker->randomFloat(2, 60, 100),
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
        ]);
        $productTwo = $this->fakeProduct([
            'price' => $this->faker->randomFloat(2, 60, 100),
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
        ]);

        $discount = $this->fakeDiscount([
            'product_id' => $productOne['id'],
            'product_category' => null,
            'updated_at' => null,
            'active' => true,
            'type' => DiscountService::ORDER_TOTAL_AMOUNT_OFF_TYPE,
            'amount' => $this->faker->numberBetween(1, 10)
        ]);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
            'min' => $this->faker->randomFloat(2, 10, 50),
            'max' => $this->faker->randomFloat(2, 50, 100), // cart total will be more than max
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaData['id'],
            'product_id' => $productOne['id'],
        ]);

        $productOneQuantity = $this->faker->numberBetween(1, 5);
        $productTwoQuantity = $this->faker->numberBetween(1, 5);

        $cartService = $this->app->make(CartService::class);

        $totalDueInItems = $productOne['price'] * $productOneQuantity + $productTwo['price'] * $productTwoQuantity;

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

        $em = $this->app->make(EcommerceEntityManager::class);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->orderTotalRequirement($discountCriteria, $totalDueInItems);

        $this->assertFalse($metCriteria);
    }

    public function test_order_shipping_total_requirement_met()
    {
        $userId = $this->createAndLogInNewUser();

        $em = $this->app->make(EcommerceEntityManager::class);

        $cart = Cart::fromSession();

        $shippingCost = $this->faker->randomFloat(2, 100, 200);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'min' => round($this->faker->randomFloat(2, 10, 90), 2),
            'max' => round($this->faker->randomFloat(2, 210, 300), 2),
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
        ]);

        $product = $this->fakeProduct([
            'price' => $this->faker->randomFloat(2, 60, 100),
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaData['id'],
            'product_id' => $product['id'],
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->orderShippingTotalRequirement($discountCriteria, $shippingCost);

        $this->assertTrue($metCriteria);
    }

    public function test_order_shipping_total_requirement_not_met()
    {
        $userId = $this->createAndLogInNewUser();

        $em = $this->app->make(EcommerceEntityManager::class);

        $cart = Cart::fromSession();

        $shippingCost = $this->faker->randomFloat(2, 100);;

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'min' => $this->faker->randomFloat(2, 10, 20),
            'max' => $this->faker->randomFloat(2, 30, 40),
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
        ]);

        $product = $this->fakeProduct([
            'price' => $this->faker->randomFloat(2, 60, 100),
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaData['id'],
            'product_id' => $product['id'],
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->orderShippingTotalRequirement($discountCriteria, $shippingCost);

        $this->assertFalse($metCriteria);
    }

    public function test_order_shipping_country_requirement_met()
    {
        $userId = $this->createAndLogInNewUser();

        $this->session->flush();

        $shippingAddress = new Address($this->faker->word, $this->faker->word);

        $cart = Cart::fromSession();

        $cart->setShippingAddress($shippingAddress);

        $em = $this->app->make(EcommerceEntityManager::class);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'min' => $shippingAddress->getCountry(),
            'max' => $this->faker->word . $this->faker->word,
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
        ]);

        $product = $this->fakeProduct([
            'price' => $this->faker->randomFloat(2, 60, 100),
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaData['id'],
            'product_id' => $product['id'],
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->orderShippingCountryRequirement($discountCriteria, $cart);

        $this->assertTrue($metCriteria);
    }

    public function test_order_shipping_country_requirement_not_met()
    {
        $userId = $this->createAndLogInNewUser();

        $this->session->flush();

        $shippingAddress = new Address($this->faker->word, $this->faker->word);

        $cart = Cart::fromSession();

        $cart->setShippingAddress($shippingAddress);

        $em = $this->app->make(EcommerceEntityManager::class);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'min' => $this->faker->word . $this->faker->word,
            'max' => $this->faker->word . $this->faker->word,
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
        ]);

        $product = $this->fakeProduct([
            'price' => $this->faker->randomFloat(2, 60, 100),
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaData['id'],
            'product_id' => $product['id'],
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->orderShippingCountryRequirement($discountCriteria, $cart);

        $this->assertFalse($metCriteria);
    }

    public function test_promo_code_requirement_met()
    {
        $userId = $this->createAndLogInNewUser();

        $em = $this->app->make(EcommerceEntityManager::class);

        $promoCode = $this->faker->word;

        $cart = Cart::fromSession();

        $cart->setPromoCode($promoCode);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'min' => $promoCode,
            'max' => $this->faker->word . $this->faker->word,
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
        ]);

        $product = $this->fakeProduct([
            'price' => $this->faker->randomFloat(2, 60, 100),
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaData['id'],
            'product_id' => $product['id'],
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->promoCodeRequirement($discountCriteria, $cart);

        $this->assertTrue($metCriteria);
    }

    public function test_promo_code_requirement_not_met()
    {
        $userId = $this->createAndLogInNewUser();

        $em = $this->app->make(EcommerceEntityManager::class);

        $promoCode = $this->faker->word;

        $cart = Cart::fromSession();

        $cart->setPromoCode($promoCode);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'min' => $this->faker->word . $this->faker->word,
            'max' => $this->faker->word . $this->faker->word,
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
        ]);

        $product = $this->fakeProduct([
            'price' => $this->faker->randomFloat(2, 60, 100),
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaData['id'],
            'product_id' => $product['id'],
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->promoCodeRequirement($discountCriteria, $cart);

        $this->assertFalse($metCriteria);
    }

    public function test_product_own_criteria_not_met()
    {
        $userId = $this->createAndLogInNewUser();

        $em = $this->app->make(EcommerceEntityManager::class);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'min' => 1,
            'max' => rand(),
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL
        ]);

        $product = $this->fakeProduct([
            'price' => $this->faker->randomFloat(2, 60, 100),
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaData['id'],
            'product_id' => $product['id'],
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->productOwnRequirement($discountCriteria);

        $this->assertFalse($metCriteria);
    }

    public function test_product_own_criteria()
    {
        $userId = $this->createAndLogInNewUser();

        $em = $this->app->make(EcommerceEntityManager::class);

        $product = $this->fakeProduct([
            'price' => $this->faker->randomFloat(2, 60, 100),
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
        ]);

        $userProductData = $this->fakeUserProduct([
            'user_id' => $userId,
            'product_id' => $product['id'],
        ]);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'min' => 1,
            'max' => $userProductData['quantity'],
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ANY,
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaData['id'],
            'product_id' => $userProductData['product_id'],
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->productOwnRequirement($discountCriteria);

        $this->assertTrue($metCriteria);
    }

    public function test_product_own_criteria_many_all()
    {
        $userId = $this->createAndLogInNewUser();

        $em = $this->app->make(EcommerceEntityManager::class);

        $productOne = $this->fakeProduct([
            'price' => $this->faker->randomFloat(2, 60, 100),
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
        ]);

        $userProductOneData = $this->fakeUserProduct([
            'user_id' => $userId,
            'product_id' => $productOne['id'],
            'quantity' => 2,
        ]);

        $productTwo = $this->fakeProduct([
            'price' => $this->faker->randomFloat(2, 60, 100),
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
        ]);

        $userProductTwoData = $this->fakeUserProduct([
            'user_id' => $userId,
            'product_id' => $productTwo['id'],
            'quantity' => 2,
        ]);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'min' => 1,
            'max' => 3,
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
        ]);

        $discountCriteriaProductOne = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaData['id'],
            'product_id' => $productOne['id']
        ]);

        $discountCriteriaProductTwo = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaData['id'],
            'product_id' => $productTwo['id']
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->productOwnRequirement($discountCriteria);

        $this->assertTrue($metCriteria);
    }

    public function test_product_own_criteria_many_all_not_met()
    {
        $userId = $this->createAndLogInNewUser();

        $em = $this->app->make(EcommerceEntityManager::class);

        $productOne = $this->fakeProduct([
            'price' => $this->faker->randomFloat(2, 60, 100),
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
        ]);

        $userProductOneData = $this->fakeUserProduct([
            'user_id' => $userId,
            'product_id' => $productOne['id'],
            'quantity' => 2,
        ]);

        $productTwo = $this->fakeProduct([
            'price' => $this->faker->randomFloat(2, 60, 100),
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
        ]);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'min' => 1,
            'max' => 3,
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
        ]);

        $discountCriteriaProductOne = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaData['id'],
            'product_id' => $productOne['id']
        ]);

        $discountCriteriaProductTwo = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaData['id'],
            'product_id' => $productTwo['id']
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->productOwnRequirement($discountCriteria);

        $this->assertFalse($metCriteria);
    }

    public function test_product_own_quantity_not_met()
    {
        $userId = $this->createAndLogInNewUser();

        $em = $this->app->make(EcommerceEntityManager::class);

        $product = $this->fakeProduct([
            'price' => $this->faker->randomFloat(2, 60, 100),
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
        ]);

        $userProductData = $this->fakeUserProduct([
            'user_id' => $userId,
            'product_id' => $product['id'],
        ]);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'min' => ($userProductData['quantity'] + 1),
            'max' => ($userProductData['quantity'] + 10),
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaData['id'],
            'product_id' => $userProductData['product_id'],
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->productOwnRequirement($discountCriteria);

        $this->assertFalse($metCriteria);
    }

    public function test_product_own_requirement_expired()
    {
        $userId = $this->createAndLogInNewUser();

        $em = $this->app->make(EcommerceEntityManager::class);

        $product = $this->fakeProduct([
            'price' => $this->faker->randomFloat(2, 60, 100),
            'active' => 1,
            'stock' => $this->faker->numberBetween(20, 100),
        ]);

        $userProductData = $this->fakeUserProduct([
            'user_id' => $userId,
            'product_id' => $product['id'],
            'expiration_date' => Carbon::now()->subDays(3)
        ]);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'min' => 1,
            'max' => $userProductData['quantity'],
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteriaData['id'],
            'product_id' => $userProductData['product_id'],
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->productOwnRequirement($discountCriteria);

        $this->assertFalse($metCriteria);
    }

    public function test_brand_member_requirement_met()
    {
        $userId = $this->createAndLogInNewUser();

        $em = $this->app->make(EcommerceEntityManager::class);

        $discountCriteriaData = $this->fakeDiscountCriteria(
            [
                'min' => config('ecommerce.brand'),
                'max' => config('ecommerce.brand'),
            ]
        );

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
            ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->isMemberOfBrandRequirement($discountCriteria);

        $this->assertTrue($metCriteria);
    }

    public function test_brand_member_requirement_not_met()
    {
        $userId = $this->createAndLogInNewUser();

        $em = $this->app->make(EcommerceEntityManager::class);

        $discountCriteriaData = $this->fakeDiscountCriteria(
            [
                'min' => 'invalid-brand-3',
                'max' => 'invalid-brand-3',
            ]
        );

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
            ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService->isMemberOfBrandRequirement($discountCriteria);

        $this->assertFalse($metCriteria);
    }
}
