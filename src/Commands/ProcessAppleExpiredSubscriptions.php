<?php

namespace Railroad\Ecommerce\Commands;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\AppleStoreKitService;
use Throwable;

class ProcessAppleExpiredSubscriptions extends Command
{
    protected $name = 'ecommerce:ProcessAppleExpiredSubscriptions';

    protected $description = 'Queries Apple to get the state of subscriptions due to expire';

    public function info($string, $verbosity = null)
    {
        Log::info($string); //also write info statements to log
        $this->line($string, 'info', $verbosity);
    }

    public function handle(
        AppleStoreKitService $appleStoreKitService,
        SubscriptionRepository $subscriptionRepository
    ) {
        $this->info("Processing $this->name");
        $timeStart = microtime(true);

        $subscriptions = $subscriptionRepository->getAppleExpiredSubscriptions();
        $count = count($subscriptions);
        $this->info("$count Apple Subscriptions found.");
        $i = 0;
        foreach ($subscriptions as $subscription) {
            try {
                $appleStoreKitService->processSubscriptionRenewal($subscription);
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
            $id = $subscription->getId();
            $this->info("$i Subscription $id processed");
            $i++;
        }

        $diff = microtime(true) - $timeStart;
        $sec = intval($diff);
        $this->info("Finished $this->name ($sec s)");
    }
}
