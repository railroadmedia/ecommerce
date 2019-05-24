<?php

use Carbon\Carbon;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\UserProduct;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Services\UserProductService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class UserProductServiceTest extends EcommerceTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function test_get_user_product_null()
    {
        $em = $this->app->make(EcommerceEntityManager::class);
        $srv = $this->app->make(UserProductService::class);

        $userProvider = $this->app->make(UserProviderInterface::class);

        $email = $this->faker->email;
        $password = $this->faker->shuffleString(
            $this->faker->bothify('???###???###???###???###')
        );

        $user = $userProvider->createUser($email, $password);

        $product = new Product();

        $product->setBrand($this->faker->word);
        $product->setName($this->faker->word);
        $product->setSku($this->faker->word);
        $product->setPrice($this->faker->randomNumber(4));
        $product->setType(Product::TYPE_DIGITAL_SUBSCRIPTION);
        $product->setActive(true);
        $product->setIsPhysical(false);

        $em->persist($product);
        $em->flush();

        $result = $srv->getUserProduct($user, $product);

        $this->assertNull($result);
    }

    public function test_get_user_product()
    {
        $em = $this->app->make(EcommerceEntityManager::class);
        $srv = $this->app->make(UserProductService::class);

        $userProvider = $this->app->make(UserProviderInterface::class);

        $email = $this->faker->email;
        $password = $this->faker->shuffleString(
            $this->faker->bothify('???###???###???###???###')
        );

        $user = $userProvider->createUser($email, $password);

        $product = new Product();

        $product->setBrand($this->faker->word);
        $product->setName($this->faker->word);
        $product->setSku($this->faker->word);
        $product->setPrice($this->faker->randomNumber(4));
        $product->setType(Product::TYPE_DIGITAL_SUBSCRIPTION);
        $product->setActive(true);
        $product->setIsPhysical(false);

        $em->persist($product);

        $userProduct = new UserProduct();

        $userProduct->setUser($user);
        $userProduct->setProduct($product);
        $userProduct->setQuantity(1);
        $userProduct->setCreatedAt(Carbon::now());

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
        $em = $this->app->make(EcommerceEntityManager::class);

        $srv = $this->app->make(UserProductService::class);

        $userProvider = $this->app->make(UserProviderInterface::class);

        $email = $this->faker->email;
        $password = $this->faker->shuffleString(
            $this->faker->bothify('???###???###???###???###')
        );

        $user = $userProvider->createUser($email, $password);

        $product = new Product();

        $product->setBrand($this->faker->word);
        $product->setName($this->faker->word);
        $product->setSku($this->faker->word);
        $product->setPrice($this->faker->randomNumber(4));
        $product->setType(Product::TYPE_DIGITAL_SUBSCRIPTION);
        $product->setActive(true);
        $product->setIsPhysical(false);

        $em->persist($product);

        $result = $srv->createUserProduct($user, $product, Carbon::now(), 1);

        $this->assertEquals(
            UserProduct::class,
            get_class($result)
        );

        // assert user product was created
        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'product_id' => $product->getId(),
                'user_id' => $user->getId(),
            ]
        );
    }

    public function test_assign_user_product_create()
    {
        $em = $this->app->make(EcommerceEntityManager::class);

        $srv = $this->app->make(UserProductService::class);

        $userProvider = $this->app->make(UserProviderInterface::class);

        $email = $this->faker->email;
        $password = $this->faker->shuffleString(
            $this->faker->bothify('???###???###???###???###')
        );

        $user = $userProvider->createUser($email, $password);

        $product = new Product();

        $product->setBrand($this->faker->word);
        $product->setName($this->faker->word);
        $product->setSku($this->faker->word);
        $product->setPrice($this->faker->randomNumber(4));
        $product->setType(Product::TYPE_DIGITAL_SUBSCRIPTION);
        $product->setActive(true);
        $product->setIsPhysical(false);

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
            'ecommerce_user_products',
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
        $em = $this->app->make(EcommerceEntityManager::class);

        $srv = $this->app->make(UserProductService::class);

        $userProvider = $this->app->make(UserProviderInterface::class);

        $email = $this->faker->email;
        $password = $this->faker->shuffleString(
            $this->faker->bothify('???###???###???###???###')
        );

        $user = $userProvider->createUser($email, $password);

        $product = new Product();

        $product->setBrand($this->faker->word);
        $product->setName($this->faker->word);
        $product->setSku($this->faker->word);
        $product->setPrice($this->faker->randomNumber(4));
        $product->setType(Product::TYPE_DIGITAL_SUBSCRIPTION);
        $product->setActive(true);
        $product->setIsPhysical(false);

        $em->persist($product);

        $initialQuantity = $this->faker->randomNumber(4);

        $userProduct = new UserProduct();

        $userProduct->setUser($user);
        $userProduct->setProduct($product);
        $userProduct->setQuantity($initialQuantity);
        $userProduct->setCreatedAt(
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
            'ecommerce_user_products',
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
        $em = $this->app->make(EcommerceEntityManager::class);

        $srv = $this->app->make(UserProductService::class);

        $userProvider = $this->app->make(UserProviderInterface::class);

        $email = $this->faker->email;
        $password = $this->faker->shuffleString(
            $this->faker->bothify('???###???###???###???###')
        );

        $user = $userProvider->createUser($email, $password);

        $productOne = new Product();

        $productOne->setBrand($this->faker->word);
        $productOne->setName($this->faker->word);
        $productOne->setSku($this->faker->word);
        $productOne->setPrice($this->faker->randomNumber(4));
        $productOne->setType(Product::TYPE_DIGITAL_SUBSCRIPTION);
        $productOne->setActive(true);
        $productOne->setIsPhysical(false);

        $em->persist($productOne);

        $productTwo = new Product();

        $productTwo->setBrand($this->faker->word);
        $productTwo->setName($this->faker->word);
        $productTwo->setSku($this->faker->word);
        $productTwo->setPrice($this->faker->randomNumber(4));
        $productTwo->setType(Product::TYPE_DIGITAL_SUBSCRIPTION);
        $productTwo->setActive(true);
        $productTwo->setIsPhysical(false);

        $em->persist($productTwo);

        $userProductOne = new UserProduct();

        $userProductOneQuantity = 1;

        $userProductOne->setUser($user);
        $userProductOne->setProduct($productOne);
        $userProductOne->setQuantity($userProductOneQuantity);
        $userProductOne->setExpirationDate(Carbon::now());
        $userProductOne->setCreatedAt(
            Carbon::now()->subDay($this->faker->randomNumber(4))
        );

        $em->persist($userProductOne);

        $userProductTwo = new UserProduct();

        $userProductTwoQuantity = 3;

        $userProductTwo->setUser($user);
        $userProductTwo->setProduct($productTwo);
        $userProductTwo->setQuantity($userProductTwoQuantity);
        $userProductTwo->setExpirationDate(Carbon::now());
        $userProductTwo->setCreatedAt(
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
            'ecommerce_user_products',
            [
                'product_id' => $productOne->getId(),
                'user_id' => $user->getId()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'product_id' => $productTwo->getId(),
                'user_id' => $user->getId(),
                'expiration_date' => $expirationDate->toDateTimeString(),
                'quantity' => $userProductTwoQuantity - $newUserProductTwoQuantity,
            ]
        );
    }
}
