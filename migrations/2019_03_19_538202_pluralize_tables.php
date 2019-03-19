<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class PluralizeTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('ecommerce_access_code', 'ecommerce_access_codes');
        Schema::rename('ecommerce_address', 'ecommerce_addresses');
        Schema::rename('ecommerce_credit_card', 'ecommerce_credit_cards');
        Schema::rename('ecommerce_customer', 'ecommerce_customers');
        Schema::rename('ecommerce_discount', 'ecommerce_discounts');
        Schema::rename('ecommerce_order', 'ecommerce_orders');
        Schema::rename('ecommerce_order_discount', 'ecommerce_order_discounts');
        Schema::rename('ecommerce_order_item', 'ecommerce_order_items');
        Schema::rename('ecommerce_order_payment', 'ecommerce_order_payments');
        Schema::rename('ecommerce_payment', 'ecommerce_payments');
        Schema::rename('ecommerce_payment_method', 'ecommerce_payment_methods');
        Schema::rename('ecommerce_paypal_billing_agreement', 'ecommerce_paypal_billing_agreements');
        Schema::rename('ecommerce_product', 'ecommerce_products');
        Schema::rename('ecommerce_refund', 'ecommerce_refunds');
        Schema::rename('ecommerce_shipping_costs_weight_range', 'ecommerce_shipping_costs_weight_ranges');
        Schema::rename('ecommerce_shipping_option', 'ecommerce_shipping_options');
        Schema::rename('ecommerce_subscription', 'ecommerce_subscriptions');
        Schema::rename('ecommerce_subscription_access_code', 'ecommerce_subscription_access_codes');
        Schema::rename('ecommerce_subscription_payment', 'ecommerce_subscription_payments');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('ecommerce_access_codes', 'ecommerce_access_code');
        Schema::rename('ecommerce_addresses', 'ecommerce_address');
        Schema::rename('ecommerce_credit_cards', 'ecommerce_credit_card');
        Schema::rename('ecommerce_customers', 'ecommerce_customer');
        Schema::rename('ecommerce_discounts', 'ecommerce_discount');
        Schema::rename('ecommerce_orders', 'ecommerce_order');
        Schema::rename('ecommerce_order_discounts', 'ecommerce_order_discount');
        Schema::rename('ecommerce_order_items', 'ecommerce_order_item');
        Schema::rename('ecommerce_order_payments', 'ecommerce_order_payment');
        Schema::rename('ecommerce_payments', 'ecommerce_payment');
        Schema::rename('ecommerce_payment_methods', 'ecommerce_payment_method');
        Schema::rename('ecommerce_paypal_billing_agreements', 'ecommerce_paypal_billing_agreement');
        Schema::rename('ecommerce_products', 'ecommerce_product');
        Schema::rename('ecommerce_refunds', 'ecommerce_refund');
        Schema::rename('ecommerce_shipping_costs_weight_ranges', 'ecommerce_shipping_costs_weight_range');
        Schema::rename('ecommerce_shipping_options', 'ecommerce_shipping_option');
        Schema::rename('ecommerce_subscriptions', 'ecommerce_subscription');
        Schema::rename('ecommerce_subscription_access_codes', 'ecommerce_subscription_access_code');
        Schema::rename('ecommerce_subscription_payments', 'ecommerce_subscription_payment');
    }
}
























