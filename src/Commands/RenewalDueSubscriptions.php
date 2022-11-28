<?php

namespace Railroad\Ecommerce\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
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

    protected $name = 'renewalDueSubscriptions';
    protected $description = 'Renewal of due subscriptions.';

    public function info($string, $verbosity = null)
    {
        Log::info($string); //also write info statements to log
        $this->line($string, 'info', $verbosity);
    }

    public function handle(
        EcommerceEntityManager $entityManager,
        SubscriptionRepository $subscriptionRepository,
        SubscriptionService $subscriptionService
    ) {
        $this->info("Processing $this->name");
        $timeStart = microtime(true);
        $entityManager->getFilters()->disable('soft-deleteable');

        $tStart = microtime(true);
        $dueSubscriptions = $subscriptionRepository->getSubscriptionsDueToRenew();
        $this->info('Query time: ' . (microtime(true) - $tStart));

        $this->info('Attempting to renew subscriptions. Count: ' . count($dueSubscriptions));

        foreach ($dueSubscriptions as $dueSubscription) {
            $this->info("Memory usage: " . (memory_get_peak_usage(true) / 1024 / 1024) . " MB");

            /** @var $dueSubscription Subscription */

            $oldSubscriptionState = clone($dueSubscription);

            try {
                $payment = $subscriptionService->renew($dueSubscription);

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

                // if its the last attempt configured and it fails, automatically cancel the subscription
                // The renewal attempted number is the count of the NEXT try. So if its '3', this means 2 payment
                // attempts have already been tried and the 3rd is scheduled.
                if (!empty(config('ecommerce.subscriptions_renew_cycles')) &&
                    is_array(config('ecommerce.subscriptions_renew_cycles')) &&
                    $dueSubscription->getType() !== Subscription::TYPE_PAYMENT_PLAN &&
                    $dueSubscription->getRenewalAttempt() > count(config('ecommerce.subscriptions_renew_cycles'))) {
                    $dueSubscription->setCanceledOn(Carbon::now());
                    $dueSubscription->setIsActive(false);
                    $dueSubscription->setCancellationReason('All renewal attempts failed.');

                    $entityManager->flush();

                    $this->info(
                        'Cancelling subscription due to max attempts tried ID: ' . $dueSubscription->getId(
                        ) . ' - ' . $throwable->getMessage()
                    );
                }

                $this->info(
                    'Failed to renew subscription ID: ' . $dueSubscription->getId() . ' - ' . $throwable->getMessage()
                );
            }
        }

        $entityManager->flush();
        $entityManager->getFilters()->enable('soft-deleteable');

        $diff = microtime(true) - $timeStart;
        $sec = intval($diff);
        $this->info("Finished $this->name ($sec s)");
    }
}