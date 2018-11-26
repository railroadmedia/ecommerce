<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\DiscountCriteriaRepository;
use Railroad\Ecommerce\Repositories\DiscountRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\ShippingCostsRepository;
use Railroad\Ecommerce\Repositories\ShippingOptionRepository;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class OrderFormControllerTest extends EcommerceTestCase
{
    /**
     * @var \Railroad\Ecommerce\Repositories\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\ShippingOptionRepository
     */
    protected $shippingOptionRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\ShippingCostsRepository
     */
    protected $shippingCostsRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\DiscountRepository
     */
    protected $discountRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\DiscountCriteriaRepository
     */
    protected $discountCriteriaRepository;

    /**
     * @var CartService
     */
    protected $cartService;

    protected function setUp()
    {
        parent::setUp();
        $this->productRepository = $this->app->make(ProductRepository::class);
        $this->shippingOptionRepository = $this->app->make(ShippingOptionRepository::class);
        $this->shippingCostsRepository = $this->app->make(ShippingCostsRepository::class);
        $this->cartService = $this->app->make(CartService::class);
        $this->discountCriteriaRepository = $this->app->make(DiscountCriteriaRepository::class);
        $this->discountRepository = $this->app->make(DiscountRepository::class);
    }

    public function test_submit_order()
    {
        $userId = $this->createAndLogInNewUser();

        $shippingOption = $this->shippingOptionRepository->create(
            $this->faker->shippingOption(
                [
                    'country' => 'Canada',
                    'active' => 1,
                    'priority' => 1,
                ]
            )
        );

        $shippingCost = $this->shippingCostsRepository->create(
            $this->faker->shippingCost(
                [
                    'shipping_option_id' => $shippingOption['id'],
                    'min' => 0,
                    'max' => 10,
                    'price' => 5.50,
                ]
            )
        );

        $product1 = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 12.95,
                    'type' => ConfigService::$typeProduct,
                    'active' => 1,
                    'description' => $this->faker->word,
                    'is_physical' => 1,
                    'weight' => 0.20,
                    'subscription_interval_type' => '',
                    'subscription_interval_count' => '',
                ]
            )
        );

        $product2 = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 247,
                    'type' => ConfigService::$typeProduct,
                    'active' => 1,
                    'description' => $this->faker->word,
                    'is_physical' => 0,
                    'weight' => 0,
                    'subscription_interval_type' => ConfigService::$intervalTypeYearly,
                    'subscription_interval_count' => 1,
                ]
            )
        );
        $discount = $this->discountRepository->create(
            $this->faker->discount(
                [
                    'active' => true,
                    'amount' => 10,
                    'type' => 'product amount off',
                    'product_id' => 1,
                ]
            )
        );
        $discountCriteria = $this->discountCriteriaRepository->create(
            $this->faker->discountCriteria(
                [
                    'discount_id' => $discount['id'],
                    'product_id' => $product1['id'],
                    'type' => 'product quantity requirement',
                    'min' => '1',
                    'max' => '2000000',
                ]
            )
        );
//        $cart = $this->cartService->addCartItem(
//            $product1['name'],
//            $product1['description'],
//            1,
//            $product1['price'],
//            $product1['is_physical'],
//            $product1['is_physical'],
//            $this->faker->word,
//            rand(),
//            $product1['weight'],
//            [
//                'product-id' => $product1['id'],
//            ]
//        );
//
//        $this->cartService->addCartItem(
//            $product2['name'],
//            $product2['description'],
//            1,
//            $product2['price'],
//            $product2['is_physical'],
//            $product2['is_physical'],
//            $this->faker->word,
//            rand(),
//            $product2['weight'],
//            [
//                'product-id' => $product2['id'],
//            ]
//        );
        $cart = $this->cartService->addCartItem(
            $product1['name'],
            $product1['description'],
            1,
            $product1['price'],
            $product1['is_physical'],
            $product1['is_physical'],
            $this->faker->word,
            rand(),
            $product1['weight'],
            [
                'product-id' => $product1['id'],
            ]
        );

        $this->cartService->addCartItem(
            $product2['name'],
            $product2['description'],
            1,
            $product2['price'],
            $product2['is_physical'],
            $product2['is_physical'],
            $this->faker->word,
            rand(),
            $product2['weight'],
            [
                'product-id' => $product2['id'],
            ]
        );

        $billingAgreementId = rand(1,100);

        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')
            ->willReturn($billingAgreementId);

        $transactionId = rand(1,100);

        $this->paypalExternalHelperMock->method('createReferenceTransaction')
            ->willReturn($transactionId);

        $orderData = [
            'payment_method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'billing-region' => $this->faker->word,
            'billing-zip-or-postal-code' => $this->faker->postcode,
            'billing-country' => 'Canada',
            'company_name' => $this->faker->creditCardType,
            'gateway' => 'drumeo',
            'shipping-first-name' => $this->faker->firstName,
            'shipping-last-name' => $this->faker->lastName,
            'shipping-address-line-1' => $this->faker->address,
            'shipping-city' => 'Canada',
            'shipping-region' => 'ab',
            'shipping-zip-or-postal-code' => $this->faker->postcode,
            'shipping-country' => 'Canada',
        ];

        $this->session(['order-form-input' => $orderData]);

        ConfigService::$paypalAgreementFulfilledRoute = 'order.submit.paypal';

        $paypalToken = $this->faker->word;

        $response = $this->call(
            'GET',
            '/order-paypal',
            ['token' => $paypalToken]
        );

        // assert response code
        $this->assertEquals(302, $response->getStatusCode());

        // assert session results
        $response->assertSessionHas('success', true);
        $response->assertSessionHas('order');

        // assert database records
        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $product1['id'],
                'quantity' => 1,
                'expiration_date' => null,
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $product2['id'],
                'quantity' => 1,
                'expiration_date' => null,
            ]
        );
    }
}
