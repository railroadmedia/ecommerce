<?php

namespace Railroad\Ecommerce\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Throwable;

class PopulatePaymentTaxesTable extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'PopulatePaymentTaxesTable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populates ecommerce_payment_taxes table';

    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * PopulatePaymentTaxesTable constructor.
     *
     * @param DatabaseManager $databaseManager
     */
    public function __construct(
        DatabaseManager $databaseManager
    )
    {
        parent::__construct();

        $this->databaseManager = $databaseManager;
    }

    /**
     * Execute the console command.
     *
     * @throws Throwable
     */
    public function handle()
    {
        $this->info('Starting PopulatePaymentTaxesTable.');

        $done = 0;

        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_payments')
            ->orderBy('id', 'desc')
            ->chunk(
                500,
                function (Collection $rows) use (&$done) {

                    $insertData = [];

                    foreach ($rows as $payment) {

                        // cast to array
                        $paymentData = get_object_vars($payment);

                        $order = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                            ->table('ecommerce_order_payments')
                            ->select(['ecommerce_orders.*'])
                            ->leftJoin('ecommerce_orders', 'ecommerce_order_payments.order_id', '=', 'ecommerce_orders.id')
                            ->where('ecommerce_order_payments.payment_id', $paymentData['id'])
                            ->get()
                            ->first();

                        if (!empty($order)) {

                            $orderData = get_object_vars($order);

                            $addressId = $orderData['shipping_address_id'] ?? $orderData['billing_address_id'];

                            $addressCollection = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                                ->table('ecommerce_addresses')
                                ->where('id', $addressId)
                                ->get();

                            $addressData = get_object_vars($addressCollection->first());

                            $productTax = 0;
                            $shippingTax = 0;

                            if ($orderData['shipping_due']) {

                                if (!($orderData['product_due'] + $orderData['shipping_due'])) {
                                    // display warning - division by 0 in next block
                                    continue;
                                }

                                $taxRate = round($orderData['taxes_due'] / ($orderData['product_due'] + $orderData['shipping_due']), 2);
                                $productTax = round($orderData['product_due'] * $taxRate, 2);
                                $shippingTax = round($orderData['shipping_due'] * $taxRate, 2);
                            } else {
                                $productTax = $orderData['taxes_due'];
                            }

                            $insertData[] = [
                                'payment_id' => $paymentData['id'],
                                'country' => $addressData['country'],
                                'region' => $addressData['region'],
                                'product_rate' => $orderData['product_due'],
                                'shipping_rate' => $orderData['shipping_due'],
                                'product_taxes_paid' => $productTax,
                                'shipping_taxes_paid' => $shippingTax,
                                'created_at' => Carbon::now()->toDateTimeString()
                            ];
                        } else {

                            $subscription = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                                ->table('ecommerce_subscription_payments')
                                ->select(['ecommerce_subscriptions.*'])
                                ->leftJoin(
                                    'ecommerce_subscriptions',
                                    'ecommerce_subscription_payments.subscription_id', '=',
                                    'ecommerce_subscriptions.id'
                                )
                                ->where('ecommerce_subscription_payments.payment_id', $paymentData['id'])
                                ->get()
                                ->first();

                            if (!empty($subscription)) {

                                $subscriptionData = get_object_vars($subscription);

                                $address = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                                    ->table('ecommerce_payment_methods')
                                    ->select(['ecommerce_addresses.*'])
                                    ->leftJoin(
                                        'ecommerce_addresses',
                                        'ecommerce_payment_methods.billing_address_id', '=',
                                        'ecommerce_addresses.id'
                                    )
                                    ->where('ecommerce_payment_methods.id', $paymentData['payment_method_id'])
                                    ->get()
                                    ->first();

                                if ($address) {
                                    $addressData = get_object_vars($address);

                                    $insertData[] = [
                                        'payment_id' => $paymentData['id'],
                                        'country' => $addressData['country'],
                                        'region' => $addressData['region'],
                                        'product_rate' => round($subscriptionData['total_price'] - $subscriptionData['tax'], 2),
                                        'shipping_rate' => 0,
                                        'product_taxes_paid' => $subscriptionData['tax'],
                                        'shipping_taxes_paid' => 0,
                                        'created_at' => Carbon::now()->toDateTimeString()
                                    ];
                                } else {
                                    // display warning
                                }

                            } else {
                                // display warning
                            }
                        }
                    }

                    $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                        ->table('ecommerce_payment_taxes')
                        ->insert($insertData);

                    $this->info('Done: ' . ++$done);
                }
            );

        $this->info('Finished PopulatePaymentTaxesTable.');
    }
}