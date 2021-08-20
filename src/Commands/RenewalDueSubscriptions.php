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

class RenewalDueSubscriptions extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'renewalDueSubscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Renewal of due subscriptions.';

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
     * RenewalDueSubscriptions constructor.
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
        $this->info('------------------Renewal Due Subscriptions command------------------');

        $tStart = microtime(true);
        $dueSubscriptions = $this->subscriptionRepository->getSubscriptionsDueToRenew();
        $this->info('Query time: ' . (microtime(true) - $tStart));

        $this->info('Attempting to renew subscriptions. Count: ' . count($dueSubscriptions));

        foreach ($dueSubscriptions as $dueSubscription) {
            $this->info("Memory usage: " . (memory_get_peak_usage(true) / 1024 / 1024) . " MB");

            $activeSubscriptions =
                $this->subscriptionRepository->getUserActiveSubscription(
                    $dueSubscription->getUser(),
                    $dueSubscription->getBrand()
                );

            if (!empty($activeSubscriptions)) {
                // if the user has an other active subscription for this brand stop processing the current $dueSubscription
                continue;
            }

            /** @var $dueSubscription Subscription */

            $oldSubscriptionState = clone($dueSubscription);

            try {

                $payment = $this->subscriptionService->renew($dueSubscription);

                if ($payment) {
                    /** @var $payment Payment */
                    event(new CommandSubscriptionRenewed($dueSubscription, $payment));
                }

            } catch (Throwable $throwable) {

                error_log('---------------------------- RENEWAL ERROR ------------------------------------');
                error_log($throwable);

                $this->info($throwable->getMessage());
                $this->info($throwable->getTraceAsString());
                $this->info($throwable->getFile());
                $this->info($throwable->getLine());
                $this->info($throwable->getCode());

                $payment = null;

                if ($throwable instanceof PaymentFailedException) {
                    /** @var $payment Payment */
                    $payment = $throwable->getPayment();
                }

                event(new CommandSubscriptionRenewFailed($dueSubscription, $oldSubscriptionState, $payment));

                $this->info(
                    'Failed to renew subscription ID: ' . $dueSubscription->getId() . ' - ' . $throwable->getMessage()
                );
            }
        }

        $this->entityManager->flush();

        $this->info('-----------------End Renewal Due Subscriptions command-----------------------');
    }
}