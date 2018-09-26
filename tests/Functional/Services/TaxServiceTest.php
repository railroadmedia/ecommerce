<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Railroad\Ecommerce\Repositories\DiscountCriteriaRepository;
use Railroad\Ecommerce\Repositories\DiscountRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\UserProductRepository;
use Railroad\Ecommerce\Services\DiscountCriteriaService;
use Railroad\Ecommerce\Services\DiscountService;
use Railroad\Ecommerce\Services\TaxService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class TaxServiceTest extends EcommerceTestCase
{
    /**
     * @var TaxService
     */
    protected $classBeingTested;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var DiscountRepository
     */
    protected $discountRepository;

    /**
     * @var DiscountCriteriaRepository
     */
    protected $discountCriteriaRepository;

    /**
     * @var UserProductRepository
     */
    protected $userProductRepository;

    public function setUp()
    {
        parent::setUp();

        $this->classBeingTested = $this->app->make(TaxService::class);

        $this->productRepository = $this->app->make(ProductRepository::class);
        $this->discountRepository = $this->app->make(DiscountRepository::class);
        $this->discountCriteriaRepository = $this->app->make(DiscountCriteriaRepository::class);
        $this->userProductRepository = $this->app->make(UserProductRepository::class);
    }

    public function test_apply_discount_with_multiple_criteria()
    {
        $userId = $this->createAndLogInNewUser();
        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                    'is_physical' => 0,
                    'weight' => 0,
                    'subscription_interval_type' => '',
                    'subscription_interval_count' => '',
                ]
            )
        );
        $product1 = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                ]
            )
        );

        $product2 = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                ]
            )
        );

        $discount = $this->discountRepository->create(
            $this->faker->discount(
                [
                    'active' => true,
                    'type' => DiscountService::ORDER_TOTAL_PERCENT_OFF_TYPE,
                    'amount' => 50,
                ]
            )
        );

        $this->discountCriteriaRepository->create(
            $this->faker->discountCriteria(
                [
                    'discount_id' => $discount['id'],
                    'product_id' => $product1['id'],
                    'type' => DiscountCriteriaService::PRODUCT_OWN_TYPE,
                    'min' => 1,
                    'max' => 10,
                ]
            )
        );
        $this->discountCriteriaRepository->create(
            $this->faker->discountCriteria(
                [
                    'discount_id' => $discount['id'],
                    'product_id' => $product2['id'],
                    'type' => DiscountCriteriaService::PRODUCT_OWN_TYPE,
                    'min' => 1,
                    'max' => 10,
                ]
            )
        );

        $this->userProductRepository->create(
            $this->faker->userProduct(['user_id' => $userId, 'product_id' => $product1['id']])
        );

        $this->userProductRepository->create(
            $this->faker->userProduct(['user_id' => $userId, 'product_id' => $product2['id']])
        );

        $response = $this->classBeingTested->calculateTaxesForCartItems(
            [
                [
                    "quantity" => 1,
                    "totalPrice" => $product['price'],
                    "options" => [
                        "product-id" => $product['id'],
                    ],
                ],
            ],
            $this->faker->word,
            $this->faker->word
        );

        //assert discount amount it's applied
        $this->assertEquals($product['price'] * $discount['amount'] / 100, $response['totalDue']);
    }

    public function test_discount_not_applied(){
        $userId = $this->createAndLogInNewUser();
        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                    'is_physical' => 0,
                    'weight' => 0,
                    'subscription_interval_type' => '',
                    'subscription_interval_count' => '',
                ]
            )
        );
        $product1 = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                ]
            )
        );

        $product2 = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                ]
            )
        );

        $discount = $this->discountRepository->create(
            $this->faker->discount(
                [
                    'active' => true,
                    'type' => DiscountService::ORDER_TOTAL_PERCENT_OFF_TYPE,
                    'amount' => 50,
                ]
            )
        );

        $this->discountCriteriaRepository->create(
            $this->faker->discountCriteria(
                [
                    'discount_id' => $discount['id'],
                    'product_id' => $product1['id'],
                    'type' => DiscountCriteriaService::PRODUCT_OWN_TYPE,
                    'min' => 1,
                    'max' => 10,
                ]
            )
        );
        $this->discountCriteriaRepository->create(
            $this->faker->discountCriteria(
                [
                    'discount_id' => $discount['id'],
                    'product_id' => $product2['id'],
                    'type' => DiscountCriteriaService::PRODUCT_OWN_TYPE,
                    'min' => 1,
                    'max' => 10,
                ]
            )
        );

        //user own only one product
        $this->userProductRepository->create(
            $this->faker->userProduct(['user_id' => $userId, 'product_id' => $product1['id']])
        );

        $response = $this->classBeingTested->calculateTaxesForCartItems(
            [
                [
                    "quantity" => 1,
                    "totalPrice" => $product['price'],
                    "options" => [
                        "product-id" => $product['id'],
                    ],
                ],
            ],
            $this->faker->word,
            $this->faker->word
        );

        //assert discount amount it's not applied to the order, totalDue = product price * quantity
        $this->assertEquals($product['price'], $response['totalDue']);
    }

    public function test_discount_on_product_discounts_other_products_own(){
        $userId = $this->createAndLogInNewUser();
        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => $this->faker->numberBetween(10),
                    'active' => 1,
                    'is_physical' => 0,
                    'weight' => 0,
                    'subscription_interval_type' => '',
                    'subscription_interval_count' => '',
                ]
            )
        );
        $product1 = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                ]
            )
        );

        $product2 = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                ]
            )
        );

        $discount = $this->discountRepository->create(
            $this->faker->discount(
                [
                    'active' => true,
                    'product_id' => $product['id'],
                    'type' => DiscountService::PRODUCT_AMOUNT_OFF_TYPE,
                    'amount' => $this->faker->numberBetween(1, 9),
                ]
            )
        );

        $this->discountCriteriaRepository->create(
            $this->faker->discountCriteria(
                [
                    'discount_id' => $discount['id'],
                    'product_id' => $product1['id'],
                    'type' => DiscountCriteriaService::PRODUCT_OWN_TYPE,
                    'min' => 1,
                    'max' => 10,
                ]
            )
        );
        $this->discountCriteriaRepository->create(
            $this->faker->discountCriteria(
                [
                    'discount_id' => $discount['id'],
                    'product_id' => $product2['id'],
                    'type' => DiscountCriteriaService::PRODUCT_OWN_TYPE,
                    'min' => 1,
                    'max' => 10,
                ]
            )
        );

        //user own required products
        $this->userProductRepository->create(
            $this->faker->userProduct(['user_id' => $userId, 'product_id' => $product1['id']])
        );

        $this->userProductRepository->create(
            $this->faker->userProduct(['user_id' => $userId, 'product_id' => $product2['id']])
        );

        $response = $this->classBeingTested->calculateTaxesForCartItems(
            [
                [
                    "quantity" => 1,
                    "price" => $product['price'],
                    "totalPrice" => $product['price'],
                    "options" => [
                        "product-id" => $product['id'],
                    ],
                ],
            ],
            $this->faker->word,
            $this->faker->word
        );

        //assert discount amount it's applied to the order
        $this->assertEquals($product['price'] - $discount['amount'], $response['totalDue']);
    }



}
