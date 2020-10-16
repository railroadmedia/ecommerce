<?php

namespace Railroad\Ecommerce\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Railroad\Ecommerce\Entities\Subscription;

class SynchOrdersWithPaymentPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'SynchOrdersWithPaymentPlans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reads payments linked to payment plans and updates orders total paid and payment links';

    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * AddPastMembershipStats constructor.
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
        $start = microtime(true);

        $this->info('Started updating payment plan orders');

        $done = 0;
        $readChunkSize = 500;
        $insertChunkSize = 1000;
        $insertData = [];

        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_subscriptions')
            ->select(['ecommerce_subscriptions.*', 'ecommerce_orders.total_paid'])
            ->join(
                'ecommerce_orders',
                'ecommerce_subscriptions.order_id',
                '=',
                'ecommerce_orders.id'
            )
            ->where('type', Subscription::TYPE_PAYMENT_PLAN)
            ->whereNull('product_id')
            ->whereNotNull('order_id')
            ->orderBy('ecommerce_subscriptions.id', 'desc')
            ->chunk(
                $readChunkSize,
                function (Collection $rows) use (&$done, &$insertData, $insertChunkSize) {

                    $now = Carbon::now()
                        ->toDateTimeString();

                    foreach ($rows as $subscription) {

                        $subscriptionPayments = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                            ->table('ecommerce_subscription_payments')
                            ->select([
                                'ecommerce_subscription_payments.*',
                                'ecommerce_payments.total_paid',
                                'ecommerce_payments.total_refunded'
                            ])
                            ->join(
                                'ecommerce_payments',
                                'ecommerce_subscription_payments.payment_id',
                                '=',
                                'ecommerce_payments.id'
                            )
                            ->where('subscription_id', $subscription->id)
                            ->get();

                        $paymentIds = [];
                        $paid = 0;

                        foreach ($subscriptionPayments as $subscriptionPayment) {
                            $paymentIds[$subscriptionPayment->payment_id] = true;
                            $paid += $subscriptionPayment->total_paid - ($subscriptionPayment->total_refunded ?: 0);
                        }

                        if ($paid != $subscription->total_paid) {
                            $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                                ->table('ecommerce_orders')
                                ->where('id', $subscription->order_id)
                                ->update(['total_paid' => $paid]);
                        }

                        $orderPayments = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                            ->table('ecommerce_order_payments')
                            ->where('order_id', $subscription->order_id)
                            ->get();

                        foreach ($orderPayments as $orderPayment) {
                            if (isset($paymentIds[$orderPayment->payment_id])) {
                                unset($paymentIds[$orderPayment->payment_id]);
                            }
                        }

                        foreach ($paymentIds as $paymentId => $nil) {
                            $insertData[] = [
                                'order_id' => $subscription->order_id,
                                'payment_id' => $paymentId,
                                'created_at' => $now,
                                'updated_at' => null,
                            ];
                        }

                        if (count($insertData) >= $insertChunkSize) {

                            $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                                ->table('ecommerce_order_payments')
                                ->insert($insertData);

                            $insertData = [];
                        }
                    }
                }
            );
        
        if (count($insertData)) {
            $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                ->table('ecommerce_order_payments')
                ->insert($insertData);
        }

        $finish = microtime(true) - $start;

        $format = "Finished updating payment plan orders in total %s seconds\n";

        $this->info(sprintf($format, $finish));
    }
}
