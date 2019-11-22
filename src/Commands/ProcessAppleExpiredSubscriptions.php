<?php

namespace Railroad\Ecommerce\Commands;

use Carbon\Carbon;
use Exception;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\AppleStoreKitService;

class ProcessAppleExpiredSubscriptions extends \Illuminate\Console\Command
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
     * @var AppleStoreKitService
     */
    private $appleStoreKitService;

    /**
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * ProcessAppleExpiredSubscriptions constructor.
     *
     * @param AppleStoreKitService $appleStoreKitService
     * @param SubscriptionRepository $subscriptionRepository
     */
    public function __construct(
        AppleStoreKitService $appleStoreKitService,
        SubscriptionRepository $subscriptionRepository
    )
    {
        parent::__construct();

        $this->appleStoreKitService = $appleStoreKitService;
        $this->subscriptionRepository = $subscriptionRepository;
    }

    /**
     * Execute the console command.
     *
     * @throws Throwable
     */
    public function handle()
    {
        $this->info('------------------Process Apple Expired Subscriptions command------------------');

        $subscriptions = $this->subscriptionRepository->getAppleExpiredSubscriptions();

        foreach ($subscriptions as $subscription) {
            try {
                $this->appleStoreKitService->processSubscriptionRenewal($subscription);
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
        }

        $this->info('-----------------End Process Apple Expired Subscriptions command-----------------------');
    }
}
