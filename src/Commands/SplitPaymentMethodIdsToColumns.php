<?php

namespace Railroad\Ecommerce\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Throwable;

class SplitPaymentMethodIdsToColumns extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'SplitPaymentMethodIdsToColumns';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Take values in the payment_method table method_id column and split them to the properly ' .
    'named columns, credit_cart_id, paypal_billing_agreement_id.';

    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * RenewalDueSubscriptions constructor.
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
        $this->info('Starting SplitPaymentMethodIdsToColumns.');

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

                    foreach ($rows as $row) {
                        if ($row->method_type == PaymentMethod::TYPE_CREDIT_CARD) {
                            $creditCard = $creditCardRows[$row->method_id] ?? null;

                            if (!empty($creditCard)) {
                                $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                                    ->table('ecommerce_payment_methods')
                                    ->where('id', $row->id)
                                    ->update(['credit_card_id' => $row->method_id]);
                            }
                            else {
                                $this->info(
                                    'Could not migrate to credit card method, id not found. Payment method ID: ' .
                                    $row->id
                                );
                            }
                        }
                        elseif ($row->method_type == PaymentMethod::TYPE_PAYPAL) {
                            $billingAgreement = $paypalBillingAgreementRows[$row->method_id] ?? null;

                            if (!empty($billingAgreement)) {
                                $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                                    ->table('ecommerce_payment_methods')
                                    ->where('id', $row->id)
                                    ->update(['paypal_billing_agreement_id' => $row->method_id]);
                            }
                            else {
                                $this->info(
                                    'Could not migrate to paypal billing agreement method, id not found. Payment method ID: ' .
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

        $this->info('Finished SplitPaymentMethodIdsToColumns.');
    }
}