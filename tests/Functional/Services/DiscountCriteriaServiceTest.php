<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Illuminate\Session\Store;
use Railroad\Ecommerce\Entities\DiscountCriteria;
use Railroad\Ecommerce\Entities\Structures\Address;
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

        $cartService = $this->app->make(CartService::class);

        $cart = $cartService->getCart();

        $em = $this->app->make(EntityManager::class);

        // all discountCriteriaMetForOrder switch cases are tested below, except default
        $discountCriteriaData = $this->fakeDiscountCriteria([
            'type' => $this->faker->word . $this->faker->word,
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->discountCriteriaMetForOrder($cart, $discountCriteria);

        $this->assertFalse($metCriteria);
    }

    public function test_product_quantity_requirement_met()
    {
        $this->session->flush();

        $userId = $this->createAndLogInNewUser();

        $cartService = $this->app->make(CartService::class);

        $productOne = $this->fakeProduct();

        $cartItemOneData = [
            'name' => $this->faker->word,
            'description' => $this->faker->word,
            'quantity' => $this->faker->numberBetween(5, 15), // quantity is between discount criteria min/max
            'price' => $this->faker->numberBetween(20, 100),
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
            'quantity' => $this->faker->numberBetween(1, 3),
            'price' => $this->faker->numberBetween(20, 100),
            'requiresShippingAddress' => true,
            'requiresBillingAddress' => true,
            'subscriptionIntervalType' => null,
            'subscriptionIntervalCount' => null,
            'options' => ['product-id' => $productTwo['id']]
        ];

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
            'max' => $this->faker->numberBetween(15, 20)
        ]);

        $em = $this->app->make(EntityManager::class);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->productQuantityRequirementMet($cart, $discountCriteria);

        $this->assertTrue($metCriteria);
    }

    public function test_product_quantity_requirement_met_not_met()
    {
        $this->session->flush();

        $userId = $this->createAndLogInNewUser();

        $cartService = $this->app->make(CartService::class);

        $productOne = $this->fakeProduct();

        $cartItemOneData = [
            'name' => $this->faker->word,
            'description' => $this->faker->word,
            'quantity' => 2,
            'price' => 12.75,
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
            'quantity' => 3,
            'price' => 7.25,
            'requiresShippingAddress' => true,
            'requiresBillingAddress' => true,
            'subscriptionIntervalType' => null,
            'subscriptionIntervalCount' => null,
            'options' => ['product-id' => $productTwo['id']]
        ];

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
            'min' => $this->faker->numberBetween(5, 10),
            'max' => $this->faker->numberBetween(50, 100)
        ]);

        $em = $this->app->make(EntityManager::class);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->productQuantityRequirementMet($cart, $discountCriteria);

        $this->assertFalse($metCriteria);
    }

    public function test_order_date_requirement_met()
    {
        $userId = $this->createAndLogInNewUser();

        $em = $this->app->make(EntityManager::class);

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

        $em = $this->app->make(EntityManager::class);

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

        $cartService = $this->app->make(CartService::class);

        $productOne = $this->fakeProduct();

        $cartItemOneData = [
            'name' => $this->faker->word,
            'description' => $this->faker->word,
            'quantity' => 2,
            'price' => 9.45,
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
            'quantity' => 3,
            'price' => 3.80,
            'requiresShippingAddress' => true,
            'requiresBillingAddress' => true,
            'subscriptionIntervalType' => null,
            'subscriptionIntervalCount' => null,
            'options' => ['product-id' => $productTwo['id']]
        ];

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
            'product_id' => rand(),
            'min' => 10,
            'max' => 100
        ]);

        $em = $this->app->make(EntityManager::class);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->orderTotalRequirement($cart, $discountCriteria);

        $this->assertTrue($metCriteria);
    }

    public function test_order_total_requirement_not_met()
    {
        $this->session->flush();

        $userId = $this->createAndLogInNewUser();

        $cartService = $this->app->make(CartService::class);

        $productOne = $this->fakeProduct();

        $cartItemOneData = [
            'name' => $this->faker->word,
            'description' => $this->faker->word,
            'quantity' => $this->faker->numberBetween(1, 3),
            'price' => $this->faker->numberBetween(20, 100),
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
            'quantity' => $this->faker->numberBetween(1, 3),
            'price' => $this->faker->numberBetween(20, 100),
            'requiresShippingAddress' => true,
            'requiresBillingAddress' => true,
            'subscriptionIntervalType' => null,
            'subscriptionIntervalCount' => null,
            'options' => ['product-id' => $productTwo['id']]
        ];

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
            'product_id' => rand(),
            'min' => $this->faker->randomFloat(2, 10, 50),
            'max' => $this->faker->randomFloat(2, 50, 100) // cart total will be more than max
        ]);

        $em = $this->app->make(EntityManager::class);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->orderTotalRequirement($cart, $discountCriteria);

        $this->assertFalse($metCriteria);
    }

    public function test_order_shipping_total_requirement_met()
    {
        $userId = $this->createAndLogInNewUser();

        $em = $this->app->make(EntityManager::class);

        $shippingCosts = round($this->faker->randomFloat(2, 100, 200), 2);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'product_id' => rand(),
            'min' => round($this->faker->randomFloat(2, 10, 90), 2),
            'max' => round($this->faker->randomFloat(2, 210, 300), 2)
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->orderShippingTotalRequirement($discountCriteria, $shippingCosts);

        $this->assertTrue($metCriteria);
    }

    public function test_order_shipping_total_requirement_not_met()
    {
        $userId = $this->createAndLogInNewUser();

        $em = $this->app->make(EntityManager::class);

        $shippingCosts = $this->faker->randomFloat(2, 100);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'product_id' => rand(),
            'min' => $this->faker->randomFloat(2, 10, 20),
            'max' => $this->faker->randomFloat(2, 30, 40)
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->orderShippingTotalRequirement($discountCriteria, $shippingCosts);

        $this->assertFalse($metCriteria);
    }

    public function test_order_shipping_country_requirement_met()
    {
        $userId = $this->createAndLogInNewUser();

        $this->session->flush();

        $shippingAddress = new Address($this->faker->word, $this->faker->word);

        $cartAddressService = $this->app->make(CartAddressService::class);

        $cartAddressService->setAddress(
            $shippingAddress,
            CartAddressService::SHIPPING_ADDRESS_TYPE
        );

        $em = $this->app->make(EntityManager::class);

        $promoCode = $this->faker->word;

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'product_id' => rand(),
            'min' => $shippingAddress->getCountry(),
            'max' => $this->faker->word . $this->faker->word
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->orderShippingCountryRequirement($discountCriteria);

        $this->assertTrue($metCriteria);
    }

    public function test_order_shipping_country_requirement_not_met()
    {
        $userId = $this->createAndLogInNewUser();

        $this->session->flush();

        $shippingAddress = new Address($this->faker->word, $this->faker->word);

        $cartAddressService = $this->app->make(CartAddressService::class);

        $cartAddressService->setAddress(
            $shippingAddress,
            CartAddressService::SHIPPING_ADDRESS_TYPE
        );

        $em = $this->app->make(EntityManager::class);

        $promoCode = $this->faker->word;

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'product_id' => rand(),
            'min' => $this->faker->word . $this->faker->word,
            'max' => $this->faker->word . $this->faker->word
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->orderShippingCountryRequirement($discountCriteria);

        $this->assertFalse($metCriteria);
    }

    public function test_promo_code_requirement_met()
    {
        $userId = $this->createAndLogInNewUser();

        $em = $this->app->make(EntityManager::class);

        $promoCode = $this->faker->word;

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'product_id' => rand(),
            'min' => $promoCode,
            'max' => $this->faker->word . $this->faker->word
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->promoCodeRequirement($discountCriteria, $promoCode);

        $this->assertTrue($metCriteria);
    }

    public function test_promo_code_requirement_not_met()
    {
        $userId = $this->createAndLogInNewUser();

        $em = $this->app->make(EntityManager::class);

        $promoCode = $this->faker->word;

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'product_id' => rand(),
            'min' => $this->faker->word . $this->faker->word,
            'max' => $this->faker->word . $this->faker->word
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $discountCriteriaService = $this->app->make(DiscountCriteriaService::class);

        $metCriteria = $discountCriteriaService
            ->promoCodeRequirement($discountCriteria, $promoCode);

        $this->assertFalse($metCriteria);
    }

    public function test_product_own_criteria_not_met()
    {
        $userId = $this->createAndLogInNewUser();

        $em = $this->app->make(EntityManager::class);

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

        $em = $this->app->make(EntityManager::class);

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

        $em = $this->app->make(EntityManager::class);

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

        $em = $this->app->make(EntityManager::class);

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
