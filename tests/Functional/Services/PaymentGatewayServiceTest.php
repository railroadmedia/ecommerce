<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Factories\PaymentGatewayFactory;
use Railroad\Ecommerce\Factories\PaymentMethodFactory;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentGatewayService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class PaymentGatewayServiceTest extends EcommerceTestCase
{
    /**
     * @var PaymentGatewayService
     */
    private $classBeingTested;

    /**
     * @var PaymentGatewayFactory
     */
    private $paymentGatewayFactory;

    /**
     * @var PaymentMethodFactory
     */
    private $paymentMethodFactory;

    public function setUp()
    {
        parent::setUp();

        $this->classBeingTested = $this->app->make(PaymentGatewayService::class);
        $this->paymentGatewayFactory = $this->app->make(PaymentGatewayFactory::class);
        $this->paymentMethodFactory = $this->app->make(PaymentMethodFactory::class);
    }

    public function test_store()
    {
        $name = $this->faker->text;
        $results = $this->classBeingTested->store('stripe', $name, 'stripe_1');

        $this->assertEquals([
            'id' => 1,
            'brand' => ConfigService::$brand,
            'type' => 'stripe',
            'name' => $name,
            'config' => 'stripe_1',
            'created_on' => Carbon::now()->toDateTimeString(),
            'updated_on' => null
        ], $results);

        $this->assertDatabaseHas(
            ConfigService::$tablePaymentGateway,
            [
                'brand' => ConfigService::$brand,
                'type' => 'stripe',
                'name' => $name,
                'config' => 'stripe_1',
                'created_on' => Carbon::now()->toDateTimeString(),
            ]
        );
    }

    public function test_get_by_id()
    {
        $paymentGateway = $this->paymentGatewayFactory->store();
        $results = $this->classBeingTested->getById($paymentGateway['id']);

        $this->assertEquals($paymentGateway, $results);
    }

    public function test_update()
    {
        $paymentGateway = $this->paymentGatewayFactory->store();
        $newName = $this->faker->word;
        $results = $this->classBeingTested->update($paymentGateway['id'],
            [
                'name' => $newName
            ]);
        $this->assertEquals([
            'id' => $paymentGateway['id'],
            'brand' => $paymentGateway['brand'],
            'type' => $paymentGateway['type'],
            'name' => $newName,
            'config' => $paymentGateway['config'],
            'created_on' => $paymentGateway['created_on'],
            'updated_on' => Carbon::now()->toDateTimeString()
        ], $results);

        $this->assertDatabaseHas(
            ConfigService::$tablePaymentGateway,
            [
                'id' => $paymentGateway['id'],
                'brand' => $paymentGateway['brand'],
                'type' => $paymentGateway['type'],
                'name' => $newName,
                'config' => $paymentGateway['config'],
                'created_on' => $paymentGateway['created_on'],
                'updated_on' => Carbon::now()->toDateTimeString()
            ]
        );

        $this->assertDatabaseMissing(
            ConfigService::$tablePaymentGateway,
            [
                'id' => $paymentGateway['id'],
                'brand' => $paymentGateway['brand'],
                'type' => $paymentGateway['type'],
                'name' => $paymentGateway['name'],
                'config' => $paymentGateway['config'],
                'created_on' => $paymentGateway['created_on'],
                'updated_on' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_update_when_payment_gateway_not_exists()
    {
        $results = $this->classBeingTested->update(rand(),
            [
                'name' => $this->faker->word
            ]);
        $this->assertNull($results);
    }

    public function test_delete_payment_gateway_in_used()
    {
        $paymentGateway = $this->paymentGatewayFactory->store();
        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE, $paymentGateway['id']);

        $results = $this->classBeingTested->delete($paymentGateway['id']);

        $this->assertEquals(0, $results);
        $this->assertDatabaseHas(
            ConfigService::$tablePaymentGateway,
            [
                'id' => $paymentGateway['id'],
                'brand' => $paymentGateway['brand'],
                'type' => $paymentGateway['type'],
                'name' => $paymentGateway['name'],
                'config' => $paymentGateway['config'],
                'created_on' => $paymentGateway['created_on']
            ]
        );
    }

    public function test_delete()
    {
        $paymentGateway = $this->paymentGatewayFactory->store();
        $results = $this->classBeingTested->delete($paymentGateway['id']);

        $this->assertTrue($results);
        $this->assertDatabaseMissing(
            ConfigService::$tablePaymentGateway,
            [
                'id' => $paymentGateway['id'],
                'brand' => $paymentGateway['brand'],
                'type' => $paymentGateway['type'],
                'name' => $paymentGateway['name'],
                'config' => $paymentGateway['config'],
                'created_on' => $paymentGateway['created_on']
            ]
        );
    }
}
