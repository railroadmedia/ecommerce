<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Illuminate\Session\Store;
use Railroad\Ecommerce\Entities\DiscountCriteria;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Services\CartAddressService;
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

    public function test_discount_criteria_met_for_order_not_met()
    {
        $this->session->flush();

        $userId = $this->createAndLogInNewUser();

        $cart = Cart::fromSession();

        $em = $this->app->make(EcommerceEntityManager::class);

        // all discountCriteriaMetForOrder switch cases are tested below, except default
        $discountCriteriaData = $this->fakeDiscountCriteria([
            'type' => $this->faker->word . $this->faker->word,
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->discountCriteriaMetForOrder($discountCriteria, $cart);

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
            'product_id' => $productOne['id'],
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
            'product_id' => $productOne['id'],
            'min' => $this->faker->numberBetween(5, 10),
            'max' => $this->faker->numberBetween(50, 100)
        ]);

        $productOneQuantity = $this->faker->numberBetween(1, 5); // quantity is less than discount criteria min/max
        $productTwoQuantity = $this->faker->numberBetween(1, 5); // quantity is less than discount criteria min/max

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
            'product_id' => rand(),
            'min' => Carbon::now()->subDays(3),
            'max' => Carbon::now()->addDays(5)
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
            'product_id' => rand(),
            'min' => Carbon::now()->subDays(5),
            'max' => Carbon::now()->subDays(3)
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
            'product_id' => $productOne['id'],
            'min' => $this->faker->numberBetween(1, 5),
            'max' => $this->faker->numberBetween(500, 1000)
        ]);

        $productOneQuantity = $this->faker->numberBetween(1, 5);
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
            ->orderTotalRequirement($discountCriteria, $cart);

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
            'product_id' => $productOne['id'],
            'min' => $this->faker->randomFloat(2, 10, 50),
            'max' => $this->faker->randomFloat(2, 50, 100) // cart total will be more than max
        ]);

        $productOneQuantity = $this->faker->numberBetween(1, 5);
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
            ->orderTotalRequirement($discountCriteria, $cart);

        $this->assertFalse($metCriteria);
    }

    public function test_order_shipping_total_requirement_met()
    {
        $userId = $this->createAndLogInNewUser();

        $em = $this->app->make(EcommerceEntityManager::class);

        $cart = Cart::fromSession();

        $shippingCost = $this->faker->randomFloat(2, 100, 200);

        $cart->setShippingCost($shippingCost);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'product_id' => rand(),
            'min' => round($this->faker->randomFloat(2, 10, 90), 2),
            'max' => round($this->faker->randomFloat(2, 210, 300), 2)
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->orderShippingTotalRequirement($discountCriteria, $cart);

        $this->assertTrue($metCriteria);
    }

    public function test_order_shipping_total_requirement_not_met()
    {
        $userId = $this->createAndLogInNewUser();

        $em = $this->app->make(EcommerceEntityManager::class);

        $cart = Cart::fromSession();

        $shippingCost = $this->faker->randomFloat(2, 100);;

        $cart->setShippingCost($shippingCost);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'product_id' => rand(),
            'min' => $this->faker->randomFloat(2, 10, 20),
            'max' => $this->faker->randomFloat(2, 30, 40)
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->orderShippingTotalRequirement($discountCriteria, $cart);

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
            'product_id' => rand(),
            'min' => $shippingAddress->getCountry(),
            'max' => $this->faker->word . $this->faker->word
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
            'product_id' => rand(),
            'min' => $this->faker->word . $this->faker->word,
            'max' => $this->faker->word . $this->faker->word
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
            'product_id' => rand(),
            'min' => $promoCode,
            'max' => $this->faker->word . $this->faker->word
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
            'product_id' => rand(),
            'min' => $this->faker->word . $this->faker->word,
            'max' => $this->faker->word . $this->faker->word
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
            'product_id' => rand(),
            'min' => 1,
            'max' => rand()
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

        $userProductData = $this->fakeUserProduct([
            'user_id' => $userId,
            'product_id' => rand()
        ]);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'product_id' => $userProductData['product_id'],
            'min' => 1,
            'max' => $userProductData['quantity']
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->productOwnRequirement($discountCriteria);

        $this->assertTrue($metCriteria);
    }

    public function test_product_own_quantity_not_met()
    {
        $userId = $this->createAndLogInNewUser();

        $em = $this->app->make(EcommerceEntityManager::class);

        $userProductData = $this->fakeUserProduct([
            'user_id' => $userId,
            'product_id' => rand()
        ]);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'product_id' => $userProductData['product_id'],
            'min' => ($userProductData['quantity'] + 1),
            'max' => ($userProductData['quantity'] + 10)
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

        $userProductData = $this->fakeUserProduct([
            'user_id' => $userId,
            'product_id' => rand(),
            'expiration_date' => Carbon::now()->subDays(3)
        ]);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'product_id' => $userProductData['product_id'],
            'min' => 1,
            'max' => $userProductData['quantity']
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->productOwnRequirement($discountCriteria);

        $this->assertFalse($metCriteria);
    }
}
