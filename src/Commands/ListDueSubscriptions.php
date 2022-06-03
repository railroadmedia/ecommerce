<?php

namespace Railroad\Ecommerce\Commands;

use Illuminate\Console\Command;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Throwable;

class ListDueSubscriptions extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'listDueSubscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List the currently due subscriptions.';

    /**
     * Execute the console command.
     *
     * @throws Throwable
     */
    public function handle(SubscriptionRepository $subscriptionRepository)
    {
        $this->info('------------------ Listing Due Subscriptions ------------------');

        $dueSubscriptions = $subscriptionRepository->getSubscriptionsDueToRenew();

        $this->info('------------------------------------------');
        $this->info('Emails:');
        foreach ($dueSubscriptions as $dueSubscription) {
            $this->info($dueSubscription->getUser()->getEmail());
        }
        $this->info('------------------------------------------');

        $this->info('------------------------------------------');
        $this->info('Detailed:');
        foreach ($dueSubscriptions as $dueSubscription) {
            $this->info('User ID: ' . $dueSubscription->getUser()->getId());
            $this->info('User Email: ' . $dueSubscription->getUser()->getEmail());
            $this->info('Subscription ID: ' . $dueSubscription->getId());
            $this->info(
                'Subscription Product SKU: ' . (!empty($dueSubscription->getProduct()) ? $dueSubscription->getProduct(
                )->getSku() : 'Payment Plan')
            );
            $this->info('Subscription Paid Until: ' . $dueSubscription->getPaidUntil()->toDateTimeString());
            $this->info('Subscription Renewal Attempts: ' . $dueSubscription->getRenewalAttempt());
        }
        $this->info('------------------------------------------');

        $this->info('------------------ End Listing Due Subscriptions ------------------');
    }
}