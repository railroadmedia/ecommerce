<?php

namespace Railroad\Ecommerce\Tests\Functional\Commands;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Structures\Address as AddressStructure;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Services\TaxService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class PopulatePaymentTaxesTableTest extends EcommerceTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function test_command()
    {
        $brand = $this->faker->word;
        $taxService = $this->app->make(TaxService::class);

        $addressOne = $this->fakeAddress(
            [
                'country' => 'canada',
                'region' => 'alberta',
                'type' => Address::SHIPPING_ADDRESS_TYPE,
                'brand' => $brand,
            ]
        );

        $addressTwo = $this->fakeAddress(
            [
                'country' => 'canada',
                'region' => 'ontario',
                'type' => Address::BILLING_ADDRESS_TYPE,
                'brand' => $brand,
            ]
        );

        $orderOneProductDue = 128.95;
        $orderOneShippingDue = 5.75;
        $orderOneTaxesDue = $taxService->getTaxesDueTotal(
            $orderOneProductDue,
            $orderOneShippingDue,
            new AddressStructure($addressOne['country'], $addressOne['region'])
        );
        $orderOneTotalDue = round($orderOneProductDue + $orderOneShippingDue + $orderOneTaxesDue, 2);

        $paymentOne = $this->fakePayment(
            [
                'type' => Payment::TYPE_INITIAL_ORDER,
                'status' => Payment::STATUS_PAID,
                'currency' => $this->getCurrency(),
            ]
        );

        $orderOne = $this->fakeOrder(
            [
                'shipping_address_id' => $addressOne['id'],
                'billing_address_id' => $addressTwo['id'],
                'shipping_due' => $orderOneShippingDue,
                'product_due' => $orderOneProductDue,
                'taxes_due' => $orderOneTaxesDue,
                'total_due' => $orderOneTotalDue,
                'total_paid' => $orderOneTotalDue,
                'brand' => $brand,
            ]
        );

        $orderPaymentOne = $this->fakeOrderPayment(
            [
                'payment_id' => $paymentOne['id'],
                'order_id' => $orderOne['id'],
            ]
        );

        $addressThree = $this->fakeAddress(
            [
                'country' => 'canada',
                'region' => 'new brunswick',
                'type' => Address::BILLING_ADDRESS_TYPE,
                'brand' => $brand,
            ]
        );

        $orderTwoProductDue = 74.25;
        $orderTwoShippingDue = 0;
        $orderTwoTaxesDue = $taxService->getTaxesDueTotal(
            $orderTwoProductDue,
            $orderTwoShippingDue,
            new AddressStructure($addressThree['country'], $addressThree['region'])
        );
        $orderTwoTotalDue = round($orderTwoProductDue + $orderTwoShippingDue + $orderTwoTaxesDue, 2);

        $paymentTwo = $this->fakePayment(
            [
                'type' => Payment::TYPE_INITIAL_ORDER,
                'status' => Payment::STATUS_PAID,
                'currency' => $this->getCurrency(),
            ]
        );

        $orderTwo = $this->fakeOrder(
            [
                'billing_address_id' => $addressThree['id'],
                'shipping_address_id' => null,
                'shipping_due' => $orderTwoShippingDue,
                'product_due' => $orderTwoProductDue,
                'taxes_due' => $orderTwoTaxesDue,
                'total_due' => $orderTwoTotalDue,
                'total_paid' => $orderTwoTotalDue,
                'brand' => $brand,
            ]
        );

        $orderPaymentTwo = $this->fakeOrderPayment(
            [
                'payment_id' => $paymentTwo['id'],
                'order_id' => $orderTwo['id'],
            ]
        );

        //
        $addressFour = $this->fakeAddress(
            [
                'country' => 'canada',
                'region' => 'newfoundland and labrador',
                'type' => Address::BILLING_ADDRESS_TYPE,
                'brand' => $brand,
            ]
        );

        $paymentMethodOne = $this->fakePaymentMethod(
            [
                'currency' => $this->getCurrency(),
                'billing_address_id' => $addressFour['id'],
            ]
        );

        $subscriptionOnePrice = 143.25;
        $subscriptionOneTaxesDue = $taxService->getTaxesDueTotal(
            $subscriptionOnePrice,
            0,
            new AddressStructure($addressFour['country'], $addressFour['region'])
        );
        $subscriptionOneTotalDue = round($subscriptionOnePrice + $subscriptionOneTaxesDue, 2);

        $subscriptionOne = $this->fakeSubscription(
            [
                'payment_method_id' => $paymentMethodOne['id'],
                'brand' => $brand,
                'type' => Subscription::TYPE_SUBSCRIPTION,
                'is_active' => true,
                'start_date' => Carbon::now()
                    ->subMonths(2)
                    ->toDateTimeString(),
                'paid_until' => Carbon::now()
                    ->addMonths(4)
                    ->toDateTimeString(),
                'total_price' => $subscriptionOneTotalDue,
                'tax' => $subscriptionOneTaxesDue,
                'currency' => $paymentMethodOne['currency'],
                'interval_type' => config('ecommerce.interval_type_monthly'),
                'interval_count' => 6,
                'total_cycles_paid' => 1,
            ]
        );

        $paymentThree = $this->fakePayment(
            [
                'type' => Payment::TYPE_INITIAL_ORDER,
                'status' => Payment::STATUS_PAID,
                'currency' => $this->getCurrency(),
            ]
        );

        $subscriptionPaymentOne = $this->fakeSubscriptionPayment(
            [
                'subscription_id' => $subscriptionOne['id'],
                'payment_id' => $paymentThree['id'],
            ]
        );

        $this->artisan('PopulatePaymentTaxesTable');

        $expectedTaxRateOne = round($orderOneTaxesDue / ($orderOneProductDue + $orderOneShippingDue), 2);
        $expectedProductTaxOne = round($orderOneProductDue * $expectedTaxRateOne, 2);
        $expectedShippingTaxOne = round($orderOneShippingDue * $expectedTaxRateOne, 2);;

        $this->assertDatabaseHas(
            'ecommerce_payment_taxes',
            [
                'payment_id' => $paymentOne['id'],
                'country' => $addressOne['country'],
                'region' => $addressOne['region'],
                'product_rate' => $expectedTaxRateOne,
                'shipping_rate' => $expectedTaxRateOne,
                'product_taxes_paid' => $expectedProductTaxOne,
                'shipping_taxes_paid' => $expectedShippingTaxOne,
            ]
        );

        $expectedTaxRateTwo = round($orderTwoTaxesDue / $orderTwoProductDue, 2);
        $expectedProductTaxTwo = round($orderTwoProductDue * $expectedTaxRateTwo, 2);

        $this->assertDatabaseHas(
            'ecommerce_payment_taxes',
            [
                'payment_id' => $paymentTwo['id'],
                'country' => $addressThree['country'],
                'region' => $addressThree['region'],
                'product_rate' => $expectedTaxRateTwo,
                'shipping_rate' => 0,
                'product_taxes_paid' => $expectedProductTaxTwo,
                'shipping_taxes_paid' => 0,
            ]
        );

        $expectedTaxRateThree = round($subscriptionOneTaxesDue / $subscriptionOnePrice, 2);
        $expectedProductTaxThree = round($subscriptionOnePrice * $expectedTaxRateThree, 2);

        $this->assertDatabaseHas(
            'ecommerce_payment_taxes',
            [
                'payment_id' => $paymentThree['id'],
                'country' => $addressFour['country'],
                'region' => $addressFour['region'],
                'product_rate' => $expectedTaxRateThree,
                'shipping_rate' => 0,
                'product_taxes_paid' => $expectedProductTaxThree,
                'shipping_taxes_paid' => 0,
            ]
        );
    }
}
