<?php

use Doctrine\ORM\EntityManager;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\UserProduct;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\UserProductService;
use Railroad\Ecommerce\Entities\CreditCard;
use Carbon\Carbon;
use Stripe\Card;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Token;

class UserProductServiceTest extends EcommerceTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function test_get_user_product_null()
    {
        $em = $this->app->make(EntityManager::class);
        $srv = $this->app->make(UserProductService::class);

        $userProvider = $this->app->make(UserProviderInterface::class);

        $email = $this->faker->email;
        $password = $this->faker->shuffleString(
            $this->faker->bothify('???###???###???###???###')
        );

        $user = $userProvider->createUser($email, $password);

        $product = new Product();

        $product
            ->setBrand($this->faker->word)
            ->setName($this->faker->word)
            ->setSku($this->faker->word)
            ->setPrice($this->faker->randomNumber(4))
            ->setType(ConfigService::$typeSubscription)
            ->setActive(true)
            ->setIsPhysical(false);

        $em->persist($product);
        $em->flush();

        $result = $srv->getUserProduct($user, $product);

        $this->assertNull($result);
    }

    public function test_get_user_product()
    {
        $em = $this->app->make(EntityManager::class);
        $srv = $this->app->make(UserProductService::class);

        $userProvider = $this->app->make(UserProviderInterface::class);

        $email = $this->faker->email;
        $password = $this->faker->shuffleString(
            $this->faker->bothify('???###???###???###???###')
        );

        $user = $userProvider->createUser($email, $password);

        $product = new Product();

        $product
            ->setBrand($this->faker->word)
            ->setName($this->faker->word)
            ->setSku($this->faker->word)
            ->setPrice($this->faker->randomNumber(4))
            ->setType(ConfigService::$typeSubscription)
            ->setActive(true)
            ->setIsPhysical(false);

        $em->persist($product);

        $userProduct = new UserProduct();

        $userProduct
            ->setUser($user)
            ->setProduct($product)
            ->setQuantity(1)
            ->setCreatedAt(Carbon::now());

        $em->persist($userProduct);
        $em->flush();

        $result = $srv->getUserProduct($user, $product);

        $this->assertEquals(
            UserProduct::class,
            get_class($result)
        );

        /**
         * @var $result UserProduct
         */
        $this->assertEquals(
            $result->getId(),
            $userProduct->getId()
        );
    }

    public function test_create_user_product()
    {
        $em = $this->app->make(EntityManager::class);

        $srv = $this->app->make(UserProductService::class);

        $userProvider = $this->app->make(UserProviderInterface::class);

        $email = $this->faker->email;
        $password = $this->faker->shuffleString(
            $this->faker->bothify('???###???###???###???###')
        );

        $user = $userProvider->createUser($email, $password);

        $product = new Product();

        $product
            ->setBrand($this->faker->word)
            ->setName($this->faker->word)
            ->setSku($this->faker->word)
            ->setPrice($this->faker->randomNumber(4))
            ->setType(ConfigService::$typeSubscription)
            ->setActive(true)
            ->setIsPhysical(false);

        $em->persist($product);

        $result = $srv->createUserProduct($user, $product, Carbon::now(), 1);

        $this->assertEquals(
            UserProduct::class,
            get_class($result)
        );

        // assert user product was created
        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'product_id' => $product->getId(),
                'user_id' => $user->getId(),
            ]
        );
    }

    public function test_assign_user_product_create()
    {
        $em = $this->app->make(EntityManager::class);

        $srv = $this->app->make(UserProductService::class);

        $userProvider = $this->app->make(UserProviderInterface::class);

        $email = $this->faker->email;
        $password = $this->faker->shuffleString(
            $this->faker->bothify('???###???###???###???###')
        );

        $user = $userProvider->createUser($email, $password);

        $product = new Product();

        $product
            ->setBrand($this->faker->word)
            ->setName($this->faker->word)
            ->setSku($this->faker->word)
            ->setPrice($this->faker->randomNumber(4))
            ->setType(ConfigService::$typeSubscription)
            ->setActive(true)
            ->setIsPhysical(false);

        $em->persist($product);

        $em->flush();

        $expirationDate = Carbon::now();

        $quantity = 1;

        $result = $srv->assignUserProduct(
            $user,
            $product,
            $expirationDate,
            $quantity
        );

        // assert user product was created
        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'product_id' => $product->getId(),
                'user_id' => $user->getId(),
                'expiration_date' => $expirationDate->toDateTimeString(),
                'quantity' => $quantity,
            ]
        );
    }

    public function test_assign_user_product_update()
    {
        $em = $this->app->make(EntityManager::class);

        $srv = $this->app->make(UserProductService::class);

        $userProvider = $this->app->make(UserProviderInterface::class);

        $email = $this->faker->email;
        $password = $this->faker->shuffleString(
            $this->faker->bothify('???###???###???###???###')
        );

        $user = $userProvider->createUser($email, $password);

        $product = new Product();

        $product
            ->setBrand($this->faker->word)
            ->setName($this->faker->word)
            ->setSku($this->faker->word)
            ->setPrice($this->faker->randomNumber(4))
            ->setType(ConfigService::$typeSubscription)
            ->setActive(true)
            ->setIsPhysical(false);

        $em->persist($product);

        $initialQuantity = $this->faker->randomNumber(4);

        $userProduct = new UserProduct();

        $userProduct
            ->setUser($user)
            ->setProduct($product)
            ->setQuantity($initialQuantity)
            ->setCreatedAt(
                Carbon::now()->subDay($this->faker->randomNumber(4))
            );

        $em->persist($userProduct);
        $em->flush();

        $expirationDate = Carbon::now();

        $newQuantity = 1;

        $result = $srv->assignUserProduct(
            $user,
            $product,
            $expirationDate,
            $newQuantity
        );

        // assert user product was updated
        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'product_id' => $product->getId(),
                'user_id' => $user->getId(),
                'expiration_date' => $expirationDate->toDateTimeString(),
                'quantity' => $initialQuantity + $newQuantity,
            ]
        );
    }

    public function tests_remove_user_products()
    {
        $em = $this->app->make(EntityManager::class);

        $srv = $this->app->make(UserProductService::class);

        $userProvider = $this->app->make(UserProviderInterface::class);

        $email = $this->faker->email;
        $password = $this->faker->shuffleString(
            $this->faker->bothify('???###???###???###???###')
        );

        $user = $userProvider->createUser($email, $password);

        $productOne = new Product();

        $productOne
            ->setBrand($this->faker->word)
            ->setName($this->faker->word)
            ->setSku($this->faker->word)
            ->setPrice($this->faker->randomNumber(4))
            ->setType(ConfigService::$typeSubscription)
            ->setActive(true)
            ->setIsPhysical(false);

        $em->persist($productOne);

        $productTwo = new Product();

        $productTwo
            ->setBrand($this->faker->word)
            ->setName($this->faker->word)
            ->setSku($this->faker->word)
            ->setPrice($this->faker->randomNumber(4))
            ->setType(ConfigService::$typeSubscription)
            ->setActive(true)
            ->setIsPhysical(false);

        $em->persist($productTwo);

        $userProductOne = new UserProduct();

        $userProductOneQuantity = 1;

        $userProductOne
            ->setUser($user)
            ->setProduct($productOne)
            ->setQuantity($userProductOneQuantity)
            ->setExpirationDate(Carbon::now())
            ->setCreatedAt(
                Carbon::now()->subDay($this->faker->randomNumber(4))
            );

        $em->persist($userProductOne);

        $userProductTwo = new UserProduct();

        $userProductTwoQuantity = 3;

        $userProductTwo
            ->setUser($user)
            ->setProduct($productTwo)
            ->setQuantity($userProductTwoQuantity)
            ->setExpirationDate(Carbon::now())
            ->setCreatedAt(
                Carbon::now()->subDay($this->faker->randomNumber(4))
            );

        $em->persist($userProductTwo);

        $em->flush();

        $expirationDate = Carbon::now();

        $newUserProductOneQuantity = 1;
        $newUserProductTwoQuantity = 1;

        $productsCollection = collect([
            [
                'product' => $userProductOne,
                'quantity' => $newUserProductOneQuantity
            ],
            [
                'product' => $userProductTwo,
                'quantity' => $newUserProductTwoQuantity
            ]
        ]);

        $result = $srv->removeUserProducts(
            $user,
            $productsCollection
        );

        // assert user product was removed
        $this->assertDatabaseMissing(
            ConfigService::$tableUserProduct,
            [
                'product_id' => $productOne->getId(),
                'user_id' => $user->getId()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'product_id' => $productTwo->getId(),
                'user_id' => $user->getId(),
                'expiration_date' => $expirationDate->toDateTimeString(),
                'quantity' => $userProductTwoQuantity - $newUserProductTwoQuantity,
            ]
        );
    }
}
