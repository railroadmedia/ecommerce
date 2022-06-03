<?php

namespace Railroad\Ecommerce\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Throwable;

class FixSubscriptionTotalAndTaxes extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'FixSubscriptionTotalAndTaxes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populates ecommerce_payment_taxes table';

    /**
     * Execute the console command.
     *
     * @throws Throwable
     */
    public function handle(DatabaseManager $databaseManager)
    {
        $this->info('Starting FixSubscriptionTotalAndTaxes.');

        $done = 0;

        $databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_subscriptions')
            ->orderBy('id', 'desc')
            ->chunk(
                500,
                function (Collection $rows) use ($databaseManager, &$done) {
                    foreach ($rows as $subscription) {
                        $totalWithoutTax = round($subscription->total_price - $subscription->tax, 2);

                        if ($subscription->tax > 0) {
                            if ((($totalWithoutTax) * 100) % 100 > 0) {
                                $this->info(
                                    'Manually check subscription id: ' .
                                    $subscription->id .
                                    ' user_id: ' .
                                    $subscription->user_id
                                );
                            }

//                            continue;
                            $databaseManager->connection(config('ecommerce.database_connection_name'))
                                ->table('ecommerce_subscriptions')
                                ->where('id', $subscription->id)
                                ->update(['total_price' => $totalWithoutTax]);
                        }
                    }

                    $this->info('Done: ' . ++$done);
                }
            );

        $this->info('Finished FixSubscriptionTotalAndTaxes.');
    }
}
