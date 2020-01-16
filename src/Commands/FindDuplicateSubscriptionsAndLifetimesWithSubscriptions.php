<?php

namespace Railroad\Ecommerce\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Railroad\Ecommerce\Entities\Subscription;
use Throwable;

class FindDuplicateSubscriptionsAndLifetimesWithSubscriptions extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'FindDuplicateSubscriptionsAndLifetimesWithSubscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'FindDuplicateSubscriptionsAndLifetimesWithSubscriptions';

    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * FindDuplicateSubscriptionsAndLifetimesWithSubscriptions constructor.
     *
     * @param DatabaseManager $databaseManager
     */
    public function __construct(
        DatabaseManager $databaseManager
    ) {
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
        $this->info('Starting FindDuplicateSubscriptionsAndLifetimesWithSubscriptions.');

        $done = 0;

        // first report he dupe subs
        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_subscriptions')
            ->where('is_active', true)
            ->orderBy('id', 'desc')
            ->chunk(
                500,
                function (Collection $rows) use (&$done) {

                    foreach ($rows as $subscription) {
                        $allUsersSubscriptions = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                            ->table('ecommerce_subscriptions')
                            ->where('is_active', true)
                            ->where('type', '!=', Subscription::TYPE_PAYMENT_PLAN)
                            ->whereNotNull('product_id')
                            ->where('user_id', $subscription->user_id)
                            ->get()
                            ->groupBy('brand')
                            ->toArray();

                        foreach ($allUsersSubscriptions as $brand => $brandUsersSubscriptions) {
                            if (count($brandUsersSubscriptions) > 1) {
                                $this->info('Dupe found for user ID: ' . $subscription->user_id);
                                break;
                            }
                        }

                        $done++;
                    }

                    $this->info('Done: ' . $done);
                }
            );

        // then report the lifetimes who still have an active sub
        $allMembershipProductSkus = [];

        foreach (config('ecommerce.membership_product_syncing_info', []) as $brand => $data) {
            foreach (($data['membership_product_skus'] ?? []) as $sku) {
                $allMembershipProductSkus[] = $sku;
            }
        }

        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_user_products')
            ->join('ecommerce_products', 'ecommerce_products.id', '=', 'ecommerce_user_products.product_id')
            ->whereIn('ecommerce_products.sku', $allMembershipProductSkus)
            ->whereNull('expiration_date')
            ->whereNull('ecommerce_user_products.deleted_at')
            ->orderBy('ecommerce_user_products.id', 'desc')
            ->chunk(
                500,
                function (Collection $rows) use (&$done) {

                    foreach ($rows as $userProduct) {
                        $allUsersSubscriptions = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                            ->table('ecommerce_subscriptions')
                            ->where('is_active', true)
                            ->where('brand', $userProduct->brand)
                            ->where('type', '!=', Subscription::TYPE_PAYMENT_PLAN)
                            ->whereNotNull('product_id')
                            ->where('user_id', $userProduct->user_id)
                            ->get()
                            ->toArray();

                        if (count($allUsersSubscriptions) > 1) {
                            $this->info('Lifetime user with subscription found for user ID: ' . $userProduct->user_id);
                            break;
                        }

                        $done++;
                    }

                    $this->info('Done: ' . $done);
                }
            );

        $this->info('Finished FindDuplicateSubscriptionsAndLifetimesWithSubscriptions.');
    }
}
