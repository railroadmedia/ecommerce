<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Railroad\Ecommerce\Factories\CartFactory;
use Railroad\Ecommerce\Factories\PaymentGatewayFactory;
use Railroad\Ecommerce\Factories\ProductFactory;
use Railroad\Ecommerce\Factories\ShippingCostsFactory;
use Railroad\Ecommerce\Factories\ShippingOptionFactory;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\OrderFormService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\PaymentService;
use Railroad\Ecommerce\Services\ProductService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class OrderFormServiceTest extends EcommerceTestCase
{
    /**
     * @var OrderFormService
     */
    private $classBeingTested;

    /**
     * @var CartFactory
     */
    private $cartFactory;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var ShippingOptionFactory
     */
    private $shippingOptionFactory;

    /**
     * @var ShippingCostsFactory
     */
    private $shippingCostFactory;

    /**
     * @var PaymentGatewayFactory
     */
    private $paymentGatewayFactory;

    public function setUp()
    {
        parent::setUp();
        $this->classBeingTested = $this->app->make(OrderFormService::class);
        $this->productFactory = $this->app->make(ProductFactory::class);
        $this->cartFactory = $this->app->make(CartFactory::class);
        $this->shippingOptionFactory = $this->app->make(ShippingOptionFactory::class);
        $this->shippingCostFactory = $this->app->make(ShippingCostsFactory::class);
        $this->paymentGatewayFactory = $this->app->make(PaymentGatewayFactory::class);
    }

    public function test_submit_order()
    {
        $shippingOption = $this->shippingOptionFactory->store('Canada', 1, 1);
        $shippingCost = $this->shippingCostFactory->store($shippingOption['id'], 0, 10, 5.50);
        $paymentGateway = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

        $product1 = $this->productFactory->store(ConfigService::$brand,
            $this->faker->word,
            $this->faker->word,
            12.95,
            ProductService::TYPE_PRODUCT,
            1,
            $this->faker->text,
            $this->faker->url,
            1,
            0.20);

        $product2 = $this->productFactory->store(ConfigService::$brand,
            $this->faker->word,
            $this->faker->word,
            247,
            ProductService::TYPE_PRODUCT,
            1,
            $this->faker->text,
            $this->faker->url,
            0,
            0);

        $cart = $this->cartFactory->addCartItem($product1['name'],
            $product1['description'],
            1,
            $product1['price'],
            $product1['is_physical'],
            $product1['is_physical'],
            $this->faker->word,
            rand(),
            $product1['weight'],
            [
                'product-id' => $product1['id']
            ]);

        $this->cartFactory->addCartItem($product2['name'],
            $product2['description'],
            1,
            $product2['price'],
            $product2['is_physical'],
            $product2['is_physical'],
            $this->faker->word,
            rand(),
            $product2['weight'],
            [
                'product-id' => $product2['id']
            ]);

        $order = $this->classBeingTested->submitOrder(
            PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'Canada',
            $this->faker->email,
            '545454',
            'ab',
            $this->faker->name,
            $this->faker->name,
            $this->faker->address,
        '',
            $this->faker->city,
           // $this->faker->word,
            'ab',
            'Canada',
            $this->faker->postcode,
            '',
            null,
        null,
            11,
            2019,
            '4242424242424242',
            '1234',
            $paymentGateway['id']
            );
        dd($order);
    }
}
