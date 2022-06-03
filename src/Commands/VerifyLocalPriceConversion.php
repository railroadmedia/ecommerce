<?php

namespace Railroad\Ecommerce\Commands;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Railroad\Ecommerce\ExternalHelpers\CurrencyConversion;
use Throwable;

class VerifyLocalPriceConversion extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'VerifyLocalPriceConversion';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify local price conversion and update subscription/payments prices with the correctly USD value';

    /**
     * Execute the console command.
     *
     * @throws GuzzleException
     * @throws Throwable
     */
    public function handle(
        DatabaseManager $databaseManager,
        CurrencyConversion $currencyConversionHelper
    ) {
        $this->info('Starting LocalPriceConversionVerification.');

        $start = microtime(true);

        $chunkSize = 100;

        $databaseManager->connection('musora_mysql')
            ->table('ecommerce_apple_receipts')
            ->select(
                'ecommerce_apple_receipts.id',
                'ecommerce_apple_receipts.email',
                'ecommerce_apple_receipts.local_currency',
                'ecommerce_apple_receipts.local_price',
                'ecommerce_subscriptions.total_price as subscription_price',
                'ecommerce_subscriptions.product_id',
                'ecommerce_subscriptions.id as subscription_id',
                'ecommerce_products.price',
                'ecommerce_apple_receipts.email',
                'ecommerce_apple_receipts.created_at'
            )
            ->leftJoin(
                'ecommerce_subscriptions',
                'ecommerce_apple_receipts.subscription_id',
                '=',
                'ecommerce_subscriptions.id'
            )
            ->leftJoin(
                'ecommerce_products',
                'ecommerce_subscriptions.product_id',
                '=',
                'ecommerce_products.id'
            )
            ->whereNotNull('ecommerce_apple_receipts.local_currency')
            ->where('ecommerce_apple_receipts.purchase_type', '=', 'subscription')
            ->where('ecommerce_apple_receipts.valid', '=', 1)
            ->whereNotIn('ecommerce_apple_receipts.local_currency', ["USD"])
            ->orderBy('ecommerce_apple_receipts.id', 'desc')
            ->chunk(
                $chunkSize,
                function (Collection $datas) use ($currencyConversionHelper, $databaseManager, $chunkSize) {
                    foreach ($datas as $data) {
                        $newConvertedValue = $currencyConversionHelper->convert(
                            $data->local_price,
                            $data->local_currency,
                            'USD'
                        );

                        if ($newConvertedValue > ($data->price + 45)) {
                            $this->info(
                                'Converted value(' .
                                $newConvertedValue .
                                ') greater than product price(' .
                                $data->price .
                                ') + 45 =' .
                                ($data->price + 45) . '                    Apple receipt id: ' . $data->id . ' - ' .
                                $data->local_price .
                                ' ' .
                                $data->local_currency .
                                ' email:' . $data->email . '  created at::' . $data->created_at
                            );
                        }
                        $databaseManager->connection(config('ecommerce.database_connection_name'))
                            ->table('ecommerce_subscriptions')
                            ->where('id', $data->subscription_id)
                            ->update(['total_price' => $newConvertedValue]);

                        $payments =
                            $databaseManager->connection(config('ecommerce.database_connection_name'))
                                ->table('ecommerce_payments')
                                ->select('ecommerce_payments.id')
                                ->join(
                                    'ecommerce_subscription_payments',
                                    'ecommerce_payments.id',
                                    '=',
                                    'ecommerce_subscription_payments.payment_id'
                                )
                                ->where(
                                    'ecommerce_subscription_payments.subscription_id',
                                    $data->subscription_id
                                )
                                ->get()
                                ->pluck('id')
                                ->toArray();

                        if (!empty($payments)) {
                            $databaseManager->connection(config('ecommerce.database_connection_name'))
                                ->table('ecommerce_payments')
                                ->whereIn('id', $payments)
                                ->update(
                                    [
                                        'total_due' => $newConvertedValue,
                                        'total_paid' => $newConvertedValue,
                                    ]
                                );
                        }
                    }
                }
            );

        $databaseManager->connection('musora_mysql')
            ->table('ecommerce_google_receipts')
            ->select(
                'ecommerce_google_receipts.id',
                'ecommerce_google_receipts.email',
                'ecommerce_google_receipts.local_currency',
                'ecommerce_google_receipts.local_price',
                'ecommerce_subscriptions.total_price as subscription_price',
                'ecommerce_subscriptions.product_id',
                'ecommerce_products.price',
                'ecommerce_subscriptions.id as subscription_id',
                'ecommerce_google_receipts.email',
                'ecommerce_google_receipts.created_at'
            )
            ->leftJoin(
                'ecommerce_subscriptions',
                'ecommerce_google_receipts.purchase_token',
                '=',
                'ecommerce_subscriptions.external_app_store_id'
            )
            ->leftJoin(
                'ecommerce_products',
                'ecommerce_subscriptions.product_id',
                '=',
                'ecommerce_products.id'
            )
            ->whereNotNull('ecommerce_google_receipts.local_currency')
            ->whereNotIn('ecommerce_google_receipts.local_currency', ["USD"])
            ->where('ecommerce_google_receipts.purchase_type', '=', 'subscription')
            ->where('ecommerce_google_receipts.valid', '=', 1)
            ->orderBy('ecommerce_google_receipts.id', 'desc')
            ->chunk(
                $chunkSize,
                function (Collection $datas) use ($databaseManager, $currencyConversionHelper, $chunkSize) {
                    foreach ($datas as $data) {
                        $newConvertedValue =
                            $currencyConversionHelper->convert($data->local_price, $data->local_currency, 'USD');

                        if ($newConvertedValue > ($data->price + 45)) {
                            $this->info(
                                'Converted value(' .
                                $newConvertedValue .
                                ') greater than product price(' .
                                $data->price .
                                ') + 45 =' .
                                ($data->price + 45) .
                                '                    Google receipt id: ' .
                                $data->id .
                                ' - ' .
                                $data->local_price .
                                ' ' .
                                $data->local_currency .
                                ' email:' . $data->email . '  created at::' . $data->created_at
                            );
                        }

                        $databaseManager->connection(config('ecommerce.database_connection_name'))
                            ->table('ecommerce_subscriptions')
                            ->where('id', $data->subscription_id)
                            ->update(['total_price' => $newConvertedValue]);

                        $payments =
                            $databaseManager->connection(config('ecommerce.database_connection_name'))
                                ->table('ecommerce_payments')
                                ->select('ecommerce_payments.id')
                                ->join(
                                    'ecommerce_subscription_payments',
                                    'ecommerce_payments.id',
                                    '=',
                                    'ecommerce_subscription_payments.payment_id'
                                )
                                ->where('ecommerce_subscription_payments.subscription_id', $data->subscription_id)
                                ->get()
                                ->pluck('id')
                                ->toArray();

                        if (!empty($payments)) {
                            $databaseManager->connection(config('ecommerce.database_connection_name'))
                                ->table('ecommerce_payments')
                                ->whereIn('id', $payments)
                                ->update(
                                    [
                                        'total_due' => $newConvertedValue,
                                        'total_paid' => $newConvertedValue,
                                    ]
                                );
                        }
                    }
                }
            );

        $finish = microtime(true) - $start;

        $format = "Finished LocalPriceConversionVerification command execution in %s seconds\n";

        $this->info(sprintf($format, $finish));
    }
}
