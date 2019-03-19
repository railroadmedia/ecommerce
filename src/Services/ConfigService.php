<?php

namespace Railroad\Ecommerce\Services;

class ConfigService
{
    /**
     * @var int
     */
    public static $cacheTime;

    /**
     * @var string
     */
    public static $databaseConnectionName;

    /**
     * @var string
     */
    public static $connectionMaskPrefix;

    /**
     * @var string
     */
    public static $dataMode;

    /**
     * @var string
     */
    public static $tablePrefix;

    /**
     * @var string
     */
    public static $tableProduct;

    /**
     * @var string
     */
    public static $tableOrder;

    /**
     * @var string
     */
    public static $tableOrderItem;

    /**
     * @var string
     */
    public static $tableAddress;

    /**
     * @var string
     */
    public static $tableCustomer;

    /**
     * @var string
     */
    public static $tableOrderPayment;

    /**
     * @var string
     */
    public static $tablePayment;

    /**
     * @var string
     */
    public static $tablePaymentMethod;

    /**
     * @var string
     */
    public static $tableCreditCard;

    /**
     * @var string
     */
    public static $tableRefund;

    /**
     * @var string
     */
    public static $tableSubscription;

    /**
     * @var string
     */
    public static $tableSubscriptionPayment;

    /**
     * @var string
     */
    public static $tableDiscount;

    /**
     * @var string
     */
    public static $tableDiscountCriteria;

    /**
     * @var string
     */
    public static $tableOrderDiscount;

    /**
     * @var string
     */
    public static $tableOrderItemFulfillment;

    /**
     * @var string
     */
    public static $tableShippingOption;

    /**
     * @var string
     */
    public static $tableShippingCostsWeightRange;

    /**
     * @var string
     */
    public static $tablePaypalBillingAgreement;

    /**
     * @var string
     */
    public static $tableCustomerPaymentMethods;

    /**
     * @var string
     */
    public static $tableUserPaymentMethods;

    /**
     * @var string
     */
    public static $tableUserStripeCustomer;

    /**
     * @var  string
     */
    public static $tableCustomerStripeCustomer;

    /**
     * @var string
     */
    public static $tablePaymentGateway;

    /**
     * @var string
     */
    public static $tableUserProduct;

    /**
     * @var string
     */
    public static $tableAccessCode;

    /**
     * @var string
     */
    public static $tableSubscriptionAccessCode;

    /**
     * @var string
     */
    public static $brand;

    /**
     * @var array
     */
    public static $availableBrands;

    /**
     * @var array
     */
    public static $taxRate;

    /**
     * @var array
     */
    public static $creditCard;

    /**
     * @var array
     */

    public static $paymentGateways;

    /**
     * @var array
     */
    public static $middleware;

    /**
     * @var string
     */
    public static $typeProduct;

    /**
     * @var string
     */
    public static $typeSubscription;

    /**
     * @var string
     */
    public static $shippingAddressType;

    /**
     * @var string
     */
    public static $billingAddressType;

    /**
     * @var array
     */
    public static $supportedCurrencies;

    /**
     * @var string
     */
    public static $defaultCurrency;

    /**
     * @var string
     */
    public static $billingAddress;

    /**
     * @var string
     */
    public static $shippingAddress;

    /**
     * @var string
     */
    public static $paypalPaymentMethodType;

    /**
     * @var string
     */
    public static $creditCartPaymentMethodType;

    /**
     * @var string
     */
    public static $manualPaymentType;

    /**
     * @var string
     */
    public static $orderPaymentType;

    /**
     * @var string
     */
    public static $renewalPaymentType;

    /**
     * @var string
     */
    public static $paymentPlanType;

    /**
     * @var string
     */
    public static $intervalTypeDaily;

    /**
     * @var string
     */
    public static $intervalTypeMonthly;

    /**
     * @var string
     */
    public static $intervalTypeYearly;

    /**
     * @var string
     */
    public static $fulfillmentStatusPending;

    /**
     * @var string
     */
    public static $fulfillmentStatusFulfilled;

    /**
     * @var string
     */
    public static $paypalAgreementRoute;

    /**
     * @var string
     */
    public static $paypalAgreementFulfilledRoute;

    /*
     * @var int
     */
    public static $subscriptionRenewalDateCutoff;

    /**
     * @var int
     */
    public static $failedPaymentsBeforeDeactivation;

    /**
     * @var int
     */
    public static $defaultCurrencyConversionRates;
}