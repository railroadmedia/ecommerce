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
use Railroad\Ecommerce\Services\RenewalService;
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
     * @var RenewalService
     */
    private $renewalService;

    /**
     * RenewalDueSubscriptions constructor.
     *
     * @param EcommerceEntityManager $entityManager
     * @param RenewalService $renewalService
     * @param SubscriptionRepository $subscriptionRepository
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        RenewalService $renewalService,
        SubscriptionRepository $subscriptionRepository
    )
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->renewalService = $renewalService;
        $this->subscriptionRepository = $subscriptionRepository;
    }

    /**
     * Execute the console command.
     *
     * @throws Throwable
     */
    public function handle()
    {
        $this->info('------------------Renewal Due Subscriptions command------------------');

        $dueSubscriptions = $this->subscriptionRepository->getSubscriptionsDueToRenew();

        $this->info('Attempting to renew subscriptions. Count: ' . count($dueSubscriptions));

        foreach ($dueSubscriptions as $dueSubscription) {

            /** @var $dueSubscription Subscription */

            $oldSubscriptionState = clone($dueSubscription);

            try {

                $payment = $this->renewalService->renew($dueSubscription);

                if ($payment) {
                    /** @var $payment Payment */
                    event(new CommandSubscriptionRenewed($dueSubscription, $payment));
                }

            } catch (Throwable $throwable) {

                error_log($throwable);

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