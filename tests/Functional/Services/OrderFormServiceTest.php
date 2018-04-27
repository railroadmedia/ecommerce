<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Factories\CartFactory;
use Railroad\Ecommerce\Factories\PaymentGatewayFactory;
use Railroad\Ecommerce\Factories\ProductFactory;
use Railroad\Ecommerce\Factories\ShippingCostsFactory;
use Railroad\Ecommerce\Factories\ShippingOptionFactory;
use Railroad\Ecommerce\Services\AddressService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\OrderFormService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\PaymentService;
use Railroad\Ecommerce\Services\ProductService;
use Railroad\Ecommerce\Services\SubscriptionService;
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
        $this->classBeingTested      = $this->app->make(OrderFormService::class);
        $this->productFactory        = $this->app->make(ProductFactory::class);
        $this->cartFactory           = $this->app->make(CartFactory::class);
        $this->shippingOptionFactory = $this->app->make(ShippingOptionFactory::class);
        $this->shippingCostFactory   = $this->app->make(ShippingCostsFactory::class);
        $this->paymentGatewayFactory = $this->app->make(PaymentGatewayFactory::class);
    }

    public function test_submit_order_with_digital_products_by_user_other_country()
    {
        $userId         = $this->createAndLogInNewUser();
        $shippingOption = $this->shippingOptionFactory->store('Canada', 1, 1);
        $shippingCost   = $this->shippingCostFactory->store($shippingOption['id'], 0, 10, 5.50);
        $paymentGateway = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

        $product1 = $this->productFactory->store(ConfigService::$brand,
            $this->faker->word,
            $this->faker->word,
            12.95,
            ProductService::TYPE_PRODUCT,
            1,
            $this->faker->text,
            $this->faker->url,
            0,
            0);

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
        $billingCountry = 'Romania';
        $billingRegion  = 'Cluj';
        $billingZip     = $this->faker->postcode;
        $fingerprint    = '4242424242424242';

        $order = $this->classBeingTested->submitOrder(
            PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            $billingCountry,
            '',
            $billingZip,
            $billingRegion,
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            null,
            null,
            11,
            2019,
            $fingerprint,
            '1234',
            $paymentGateway['id']
        );

        $this->assertNotEmpty($order);
        $this->assertDatabaseHas(ConfigService::$tableOrder,
            [
                'id'          => 1,
                'due'         => $product1['price'] + $product2['price'],
                'paid'        => $product1['price'] + $product2['price'],
                'user_id'     => $userId,
                'customer_id' => null,
                'brand'       => ConfigService::$brand
            ]);

        $this->assertDatabaseHas(ConfigService::$tableOrderItem,
            [
                'order_id'       => 1,
                'product_id'     => $product1['id'],
                'quantity'       => 1,
                'initial_price'  => $product1['price'],
                'tax'            => 0,
                'shipping_costs' => 0,
                'total_price'    => $product1['price']
            ]);
        $this->assertDatabaseHas(ConfigService::$tableOrderItem,
            [
                'order_id'       => 1,
                'product_id'     => $product2['id'],
                'quantity'       => 1,
                'initial_price'  => $product2['price'],
                'tax'            => 0,
                'shipping_costs' => 0,
                'total_price'    => $product2['price']
            ]);
        $this->assertDatabaseHas(ConfigService::$tablePayment,
            [
                'due'    => $product1['price'] + $product2['price'],
                'paid'   => $product1['price'] + $product2['price'],
                'type'   => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'status' => 1
            ]);

        $this->assertDatabaseHas(ConfigService::$tableOrderPayment,
            [
                'order_id'   => 1,
                'payment_id' => 1
            ]);
        $this->assertDatabaseHas(ConfigService::$tableAddress,
            [
                'type'        => AddressService::BILLING_ADDRESS,
                'brand'       => ConfigService::$brand,
                'user_id'     => $userId,
                'customer_id' => null,
                'zip'         => $billingZip,
                'country'     => $billingCountry,
                'state'       => $billingRegion
            ]);
        $this->assertDatabaseHas(ConfigService::$tableCreditCard,
            [
                'type'        => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'fingerprint' => $fingerprint
            ]);
    }

    public function test_fulfillment_created_after_successful_payment()
    {
        $userId         = $this->createAndLogInNewUser();
        $shippingOption = $this->shippingOptionFactory->store('Canada', 1, 1);
        $shippingCost   = $this->shippingCostFactory->store($shippingOption['id'], 0, 10, 5.50);
        $paymentGateway = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

        $product = $this->productFactory->store(ConfigService::$brand,
            $this->faker->word,
            $this->faker->word,
            12.95,
            ProductService::TYPE_PRODUCT,
            1,
            $this->faker->text,
            $this->faker->url,
            1,
            0.75);

        $cart = $this->cartFactory->addCartItem($product['name'],
            $product['description'],
            1,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $this->faker->word,
            rand(),
            $product['weight'],
            [
                'product-id' => $product['id']
            ]);

        $billingCountry = 'Canada';
        $billingRegion  = 'ab';
        $billingZip     = $this->faker->postcode;
        $fingerprint    = '4242424242424242';

        $order = $this->classBeingTested->submitOrder(
            PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            $billingCountry,
            '',
            $billingZip,
            $billingRegion,
            $this->faker->name,
            $this->faker->name,
            $this->faker->address,
            '',
            $this->faker->city,
            'ab',
            'Canada',
            $this->faker->postcode,
            '',
            null,
            null,
            11,
            2019,
            $fingerprint,
            '1234',
            $paymentGateway['id']
        );

        $this->assertDatabaseHas(ConfigService::$tableOrderItemFulfillment,
            [
                'order_id'      => $order['id'],
                'order_item_id' => 1,
                'status'        => 'pending',
                'created_on'    => Carbon::now()->toDateTimeString()
            ]);
    }

    public function test_submit_order_subscription()
    {
        $userId         = $this->createAndLogInNewUser();
        $shippingOption = $this->shippingOptionFactory->store('Canada', 1, 1);
        $shippingCost   = $this->shippingCostFactory->store($shippingOption['id'], 0, 10, 5.50);
        $paymentGateway = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

        $product = $this->productFactory->store(ConfigService::$brand,
            $this->faker->word,
            $this->faker->word,
            12.95,
            ProductService::TYPE_SUBSCRIPTION,
            1,
            $this->faker->text,
            $this->faker->url,
            0,
            0,
            SubscriptionService::INTERVAL_TYPE_YEARLY,
            1);

        $cart = $this->cartFactory->addCartItem($product['name'],
            $product['description'],
            1,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $this->faker->word,
            rand(),
            $product['weight'],
            [
                'product-id' => $product['id']
            ]);

        $billingCountry = 'Canada';
        $billingRegion  = 'ab';
        $billingZip     = $this->faker->postcode;
        $fingerprint    = '4242424242424242';

        $order = $this->classBeingTested->submitOrder(
            PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            $billingCountry,
            '',
            $billingZip,
            $billingRegion,
            $this->faker->name,
            $this->faker->name,
            $this->faker->address,
            '',
            $this->faker->city,
            'ab',
            'Canada',
            $this->faker->postcode,
            '',
            null,
            null,
            11,
            2019,
            $fingerprint,
            '1234',
            $paymentGateway['id']
        );

        $this->assertDatabaseHas(ConfigService::$tableSubscription,
            [
                'order_id' => $order['id'],
                'type'     => SubscriptionService::SUBSCRIPTION_TYPE
            ]);
    }
}
