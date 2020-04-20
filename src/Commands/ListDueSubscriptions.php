<?php

namespace Railroad\Ecommerce\Commands;

use Illuminate\Console\Command;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Events\Subscriptions\CommandSubscriptionRenewed;
use Railroad\Ecommerce\Events\Subscriptions\CommandSubscriptionRenewFailed;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\SubscriptionService;
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
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * @var SubscriptionService
     */
    private $subscriptionService;

    /**
     * ListDueSubscriptions constructor.
     *
     * @param EcommerceEntityManager $entityManager
     * @param SubscriptionRepository $subscriptionRepository
     * @param SubscriptionService $subscriptionService
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        SubscriptionRepository $subscriptionRepository,
        SubscriptionService $subscriptionService
    )
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Execute the console command.
     *
     * @throws Throwable
     */
    public function handle()
    {
        $this->info('------------------ Listing Due Subscriptions ------------------');

        $dueSubscriptions = $this->subscriptionRepository->getSubscriptionsDueToRenew();

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
            $this->info('Subscription Product SKU: ' . (!empty($dueSubscription->getProduct()) ? $dueSubscription->getProduct()->getSku() : 'Payment Plan'));
            $this->info('Subscription Paid Until: ' . $dueSubscription->getPaidUntil()->toDateTimeString());
            $this->info('Subscription Renewal Attempts: ' . $dueSubscription->getRenewalAttempt());
        }
        $this->info('------------------------------------------');

        $this->info('------------------ End Listing Due Subscriptions ------------------');
    }
}