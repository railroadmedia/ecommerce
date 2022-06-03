<?php

namespace Railroad\Ecommerce\Commands;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\AppleStoreKitService;
use Throwable;

class ProcessAppleExpiredSubscriptions extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ProcessAppleExpiredSubscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queries Apple to get the state of subscriptions due to expire';

    /**
     * Execute the console command.
     *
     * @throws GuzzleException
     * @throws Throwable
     */
    public function handle(
        AppleStoreKitService $appleStoreKitService,
        SubscriptionRepository $subscriptionRepository
    ) {
        $this->info('------------------Process Apple Expired Subscriptions command------------------');

        $subscriptions = $subscriptionRepository->getAppleExpiredSubscriptions();

        foreach ($subscriptions as $subscription) {
            try {
                $appleStoreKitService->processSubscriptionRenewal($subscription);
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
        }

        $this->info('-----------------End Process Apple Expired Subscriptions command-----------------------');
    }
}
