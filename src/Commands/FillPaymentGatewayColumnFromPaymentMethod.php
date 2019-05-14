<?php

namespace Railroad\Ecommerce\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Throwable;

class FillPaymentGatewayColumnFromPaymentMethod extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'FillPaymentGatewayColumnFromPaymentMethod';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Take payment_gateway_name values in the payment_method table and set the gateway_name' .
    ' on the payments table.';

    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * RenewalDueSubscriptions constructor.
     *
     * @param DatabaseManager $databaseManager
     */
    public function __construct(DatabaseManager $databaseManager)
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
        $this->info('Starting FillPaymentGatewayColumnFromPaymentMethod.');

        $done = 0;

        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_payment_methods')
            ->orderBy('id', 'desc')
            ->chunk(
                500,
                function (Collection $rows) use (&$done) {

                    $creditCardRows =
                        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                            ->table('ecommerce_credit_cards')
                            ->whereIn(
                                'id',
                                $rows->pluck('method_id')
                                    ->toArray()
                            )
                            ->get()
                            ->keyBy('id');

                    $paypalBillingAgreementRows =
                        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                            ->table('ecommerce_paypal_billing_agreements')
                            ->whereIn(
                                'id',
                                $rows->pluck('method_id')
                                    ->toArray()
                            )
                            ->get()
                            ->keyBy('id');

                    $payments =
                        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                            ->table('ecommerce_payments')
                            ->whereIn(
                                'payment_method_id',
                                $rows->pluck('id')
                                    ->toArray()
                            )
                            ->get()
                            ->groupBy('payment_method_id');

                    foreach ($rows as $row) {
                        if ($row->method_type == PaymentMethod::TYPE_CREDIT_CARD) {
                            $creditCard = $creditCardRows[$row->method_id] ?? null;
                            $paymentsForMethod = $payments[$row->id] ?? [];

                            if (!empty($creditCard)) {
                                foreach ($paymentsForMethod as $paymentForMethod) {
                                    $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                                        ->table('ecommerce_payments')
                                        ->where('id', $paymentForMethod->id)
                                        ->update(['gateway_name' => $creditCard->payment_gateway_name]);
                                }
                            }
                            else {
                                $this->info(
                                    'Could not migrate to credit card payment gateway, id not found. Payment method ID: ' .
                                    $row->id
                                );
                            }
                        }
                        elseif ($row->method_type == PaymentMethod::TYPE_PAYPAL) {
                            $billingAgreement = $paypalBillingAgreementRows[$row->method_id] ?? null;
                            $paymentsForMethod = $payments[$row->id] ?? [];

                            if (!empty($billingAgreement)) {
                                foreach ($paymentsForMethod as $paymentForMethod) {
                                    $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                                        ->table('ecommerce_payments')
                                        ->where('id', $paymentForMethod->id)
                                        ->update(['gateway_name' => $billingAgreement->payment_gateway_name]);
                                }
                            }
                            else {
                                $this->info(
                                    'Could not migrate to paypal billing agreement payment gateway, id not found. Payment method ID: ' .
                                    $row->id
                                );
                            }
                        }
                        else {
                            $this->info(
                                'Could not migrate to method, invalid type. Payment method ID: ' . $row->id
                            );
                        }

                        $done++;
                    }

                    $this->info('Done: ' . $done);
                }
            );

        $this->info('Finished FillPaymentGatewayColumnFromPaymentMethod.');
    }
}