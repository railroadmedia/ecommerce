<?php

namespace Railroad\Ecommerce\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Railroad\Ecommerce\Commands\RenewalDueSubscriptions;
use Railroad\Ecommerce\Events\GiveContentAccess;
use Railroad\Ecommerce\Events\UserDefaultPaymentMethodEvent;
use Railroad\Ecommerce\Listeners\GiveContentAccessListener;
use Railroad\Ecommerce\Listeners\UserDefaultPaymentMethodListener;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CustomValidationRules;

class EcommerceServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->listen = [
            GiveContentAccess::class => [GiveContentAccessListener::class . '@handle'],
            UserDefaultPaymentMethodEvent::class => [UserDefaultPaymentMethodListener::class],
        ];

        parent::boot();

        $this->setupConfig();

        $this->publishes(
            [
                __DIR__ . '/../../config/ecommerce.php' => config_path('ecommerce.php'),
            ]
        );

        if (ConfigService::$dataMode == 'host') {
            $this->loadMigrationsFrom(__DIR__ . '/../../migrations');
        }

        //load package views file (email template)
        $this->loadViewsFrom(__DIR__ . '/../../views', 'ecommerce');

        //load package routes file
        $this->loadRoutesFrom(__DIR__ . '/../../routes/routes.php');

        $this->commands(
            [
                RenewalDueSubscriptions::class,
            ]
        );

        $this->app->validator->resolver(
            function ($translator, $data, $rules, $messages, $attributes) {
                return new CustomValidationRules($translator, $data, $rules, $messages, $attributes);
            }
        );
    }

    private function setupConfig()
    {
        // caching
        ConfigService::$cacheTime = config('ecommerce.cache_duration');

        // database
        ConfigService::$databaseConnectionName = config('ecommerce.database_connection_name');
        ConfigService::$connectionMaskPrefix = config('ecommerce.connection_mask_prefix');
        ConfigService::$dataMode = config('ecommerce.data_mode');

        // tables
        ConfigService::$tablePrefix = config('ecommerce.table_prefix');

        ConfigService::$tableProduct = ConfigService::$tablePrefix . 'product';
        ConfigService::$tableOrder = ConfigService::$tablePrefix . 'order';
        ConfigService::$tableOrderItem = ConfigService::$tablePrefix . 'order_item';
        ConfigService::$tableAddress = ConfigService::$tablePrefix . 'address';
        ConfigService::$tableCustomer = ConfigService::$tablePrefix . 'customer';
        ConfigService::$tableOrderPayment = ConfigService::$tablePrefix . 'order_payment';
        ConfigService::$tablePayment = ConfigService::$tablePrefix . 'payment';
        ConfigService::$tablePaymentMethod = ConfigService::$tablePrefix . 'payment_method';
        ConfigService::$tableCreditCard = ConfigService::$tablePrefix . 'credit_card';
        ConfigService::$tableRefund = ConfigService::$tablePrefix . 'refund';
        ConfigService::$tableSubscription = ConfigService::$tablePrefix . 'subscription';
        ConfigService::$tableSubscriptionPayment = ConfigService::$tablePrefix . 'subscription_payment';
        ConfigService::$tableDiscount = ConfigService::$tablePrefix . 'discount';
        ConfigService::$tableDiscountCriteria = ConfigService::$tablePrefix . 'discount_criteria';
        ConfigService::$tableOrderDiscount = ConfigService::$tablePrefix . 'order_discount';
        ConfigService::$tableOrderItemFulfillment = ConfigService::$tablePrefix . 'order_item_fulfillment';
        ConfigService::$tableShippingOption = ConfigService::$tablePrefix . 'shipping_option';
        ConfigService::$tableShippingCostsWeightRange = ConfigService::$tablePrefix . 'shipping_costs_weight_range';
        ConfigService::$tablePaypalBillingAgreement = ConfigService::$tablePrefix . 'paypal_billing_agreement';
        ConfigService::$tableCustomerPaymentMethods = ConfigService::$tablePrefix . 'customer_payment_methods';
        ConfigService::$tableUserPaymentMethods = ConfigService::$tablePrefix . 'user_payment_methods';
        ConfigService::$tableCustomerStripeCustomer = ConfigService::$tablePrefix . 'customer_stripe_customer';
        ConfigService::$tableUserStripeCustomer = ConfigService::$tablePrefix . 'user_stripe_customer';
        ConfigService::$tablePaymentGateway = ConfigService::$tablePrefix . 'payment_gateway';
        ConfigService::$tableUserProduct = ConfigService::$tablePrefix . 'user_product';
        ConfigService::$tableAccessCode = ConfigService::$tablePrefix . 'access_code';
        ConfigService::$tableSubscriptionAccessCode = ConfigService::$tablePrefix . 'subscription_access_code';

        // brand
        ConfigService::$brand = config('ecommerce.brand');
        ConfigService::$availableBrands = config('ecommerce.available_brands');

        //tax rated
        ConfigService::$taxRate = config('ecommerce.tax_rate');

        //credit card
        ConfigService::$creditCard = config('ecommerce.credit_card');

        //paypal API
        ConfigService::$paymentGateways = config('ecommerce.payment_gateways');

        // middleware
        ConfigService::$middleware = config('ecommerce.middleware', []);

        //product type
        ConfigService::$typeProduct = config('ecommerce.typeProduct');

        //subscription type
        ConfigService::$typeSubscription = config('ecommerce.typeSubscription');

        //payment plan type
        ConfigService::$paymentPlanType = config('ecommerce.typePaymentPlan');

        //shipping address type
        ConfigService::$shippingAddressType = config('ecommerce.shippingAddress');

        //billing address type
        ConfigService::$billingAddressType = config('ecommerce.billingAddress');

        //payment method types
        ConfigService::$paypalPaymentMethodType = config('ecommerce.paypalPaymentMethodType');
        ConfigService::$creditCartPaymentMethodType = config('ecommerce.creditCartPaymentMethodType');
        ConfigService::$manualPaymentType = config('ecommerce.manualPaymentMethodType');

        //payment types
        ConfigService::$orderPaymentType = config('ecommerce.orderPaymentType');
        ConfigService::$renewalPaymentType = config('ecommerce.renewalPaymentType');

        //subscription interval types
        ConfigService::$intervalTypeDaily = config('ecommerce.intervalTypeDaily');
        ConfigService::$intervalTypeMonthly = config('ecommerce.intervalTypeMonthly');
        ConfigService::$intervalTypeYearly = config('ecommerce.intervalTypeYearly');

        //shipping fulfillment status
        ConfigService::$fulfillmentStatusPending = config('ecommerce.fulfillmentStatusPending');
        ConfigService::$fulfillmentStatusFulfilled = config('ecommerce.fulfillmentStatusFulfilled');

        // currencies
        ConfigService::$supportedCurrencies = config('ecommerce.supported_currencies');
        ConfigService::$defaultCurrency = config('ecommerce.default_currency');
        ConfigService::$defaultCurrencyConversionRates = config('ecommerce.default_currency_conversion_rates');

        // paypal
        ConfigService::$paypalAgreementRoute = config('ecommerce.paypal.agreementRoute');
        ConfigService::$paypalAgreementFulfilledRoute = config('ecommerce.paypal.agreementFulfilledRoute');

        ConfigService::$subscriptionRenewalDateCutoff = config('ecommerce.subscription_renewal_date');
        ConfigService::$failedPaymentsBeforeDeactivation = config('ecommerce.failed_payments_before_de_activation');
    }
}