<?php

namespace Railroad\Ecommerce\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Railroad\Ecommerce\Entities\Subscription;

class LifeTimeValueStatisticsTool extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'LifeTimeValueStatisticsTool';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'LifeTimeValueStatisticsTool';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(DatabaseManager $databaseManager)
    {
        $count = 0;
        $userLifeTimeValueTotal = 0;
        $allPaymentIds = [];

        $users = $this->connection($databaseManager)->table('usora_users')
            ->groupBy('usora_users.id')
            ->orderBy('usora_users.id', 'asc')
            ->select(['usora_users.id'])
            ->chunk(
                500,
                function (Collection $users) use (
                    $databaseManager,
                    &$userLifeTimeValueTotal,
                    &$count,
                    &$allPaymentIds
                ) {
                    $subscriptionIds = $this->connection($databaseManager)->table('ecommerce_subscriptions')
                        ->whereIn('user_id', $users->pluck('id'))
                        ->where('type', Subscription::TYPE_SUBSCRIPTION)
                        ->pluck('id')
                        ->toArray();

                    $orderIds = $this->connection($databaseManager)->table('ecommerce_orders')
                        ->whereIn('user_id', $users->pluck('id'))
                        ->pluck('id')
                        ->toArray();

                    $paymentIds = $this->connection($databaseManager)->table('ecommerce_order_payments')
                        ->whereIn('order_id', [])
                        ->pluck('id')
                        ->toArray();

                    $paymentIds = array_merge(
                        $paymentIds,
                        $this->connection($databaseManager)->table('ecommerce_subscription_payments')
                            ->whereIn('subscription_id', $subscriptionIds)
                            ->pluck('id')
                            ->toArray()
                    );

                    $paymentIds = array_unique($paymentIds);

                    $paymentIds = array_diff($paymentIds, $allPaymentIds);

                    $allPaymentIds = array_merge($paymentIds, $allPaymentIds);

                    $payments = $this->connection($databaseManager)->table('ecommerce_payments')
                        ->whereIn('id', $paymentIds)
                        ->selectRaw($databaseManager->raw('SUM(total_paid) as total_paid'))
                        ->first();

                    $userLifeTimeValueTotal += $payments->total_paid;
                    $count += $users->count();

                    $this->info('Count: ' . $count);
                }
            );

        $this->info('User LTV: ' . ($userLifeTimeValueTotal / ($count)));
        $this->info('Total revenue: ' . $userLifeTimeValueTotal);
    }

    private function connection($databaseManager)
    {
        return $databaseManager->connection(config('ecommerce.database_connection_name'));
    }
}
