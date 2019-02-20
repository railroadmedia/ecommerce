<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Illuminate\Session\Store;
use Railroad\Ecommerce\Entities\DiscountCriteria;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\DiscountCriteriaService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Doctrine\Common\Persistence\Proxy;

class DiscountCriteriaServiceTest extends EcommerceTestCase
{
    /**
     * @var DiscountCriteriaService
     */
    protected $classBeingTested;

    /**
     * @var Store
     */
    protected $session;

    public function setUp()
    {
        parent::setUp();

        $this->classBeingTested = $this->app->make(DiscountCriteriaService::class);
        $this->session = $this->app->make(Store::class);
    }

    public function test_discount_criteria_met_for_order_met()
    {
        // todo - create test after migrating/review CartService
    }

    public function test_discount_criteria_met_for_order_not_met()
    {
        // todo - create test after migrating/review CartService
    }

    public function test_product_quantity_requirement_met()
    {
        // todo - create test after migrating/review CartService
    }

    public function test_product_quantity_requirement_met_not_met()
    {
        // todo - create test after migrating/review CartService
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

        $metCriteria = $this->classBeingTested
            ->orderDateRequirement($discountCriteria);

        $this->assertTrue($metCriteria);
    }

    public function test_order_date_requirement_not_met()
    {
        $userId = $this->createAndLogInNewUser();

        $em = $this->app->make(EntityManager::class);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'product_id' => rand(),
            'min' => Carbon::now()->subDays(5),
            'max' => Carbon::now()->subDays(3)
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $metCriteria = $this->classBeingTested
            ->orderDateRequirement($discountCriteria);

        $this->assertFalse($metCriteria);
    }

    public function test_order_total_requirement_met()
    {
        // todo - create test after migrating/review CartService
    }

    public function test_order_total_requirement_not_met()
    {
        // todo - create test after migrating/review CartService
    }

    public function test_order_shipping_total_requirement_met()
    {
        $userId = $this->createAndLogInNewUser();

        $em = $this->app->make(EntityManager::class);

        $shippingCosts = $this->faker->randomFloat(2, 100, 200);

        $discountCriteriaData = $this->fakeDiscountCriteria([
            'product_id' => rand(),
            'min' => $this->faker->randomFloat(2, 10, 200),
            'max' => $this->faker->randomFloat(2, 300)
        ]);

        $discountCriteria = $em->getRepository(DiscountCriteria::class)
                                ->find($discountCriteriaData['id']);

        $metCriteria = $this->classBeingTested
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

        $metCriteria = $this->classBeingTested
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

        $metCriteria = $this->classBeingTested
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

        $metCriteria = $this->classBeingTested
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

        $metCriteria = $this->classBeingTested
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

        $metCriteria = $this->classBeingTested
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

        $metCriteria = $this->classBeingTested
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

        $metCriteria = $this->classBeingTested
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

        $metCriteria = $this->classBeingTested
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

        $metCriteria = $this->classBeingTested
            ->productOwnRequirement($discountCriteria);

        $this->assertFalse($metCriteria);
    }
}
