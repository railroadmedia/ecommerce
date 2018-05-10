<?php

namespace Railroad\Ecommerce\Commands;

use Railroad\Ecommerce\Services\PaymentService;

class RenewalDueSubscriptions extends \Illuminate\Console\Command
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
     * @var \Railroad\Ecommerce\Services\SubscriptionService
     */
    private $subscriptionService;

    /**
     * @var \Railroad\Ecommerce\Services\PaymentService
     */
    private $paymentService;

    /**
     * Create a new command instance.
     *
     * @param \Railroad\Ecommerce\Services\SubscriptionService $subscriptionService
     */
    public function __construct(
        \Railroad\Ecommerce\Services\SubscriptionService $subscriptionService,
        PaymentService $paymentService
    ) {
        parent::__construct();
        $this->subscriptionService = $subscriptionService;
        $this->paymentService      = $paymentService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('------------------Renewal Due Subscriptions command------------------');
        $dueSubscriptions = $this->subscriptionService->renewalDueSubscriptions();

        $this->info('Attempting to renew subscriptions. Count: ' . count($dueSubscriptions));
        $pay              = [];
        foreach($dueSubscriptions as $dueSubcription)
        {
            $this->paymentService->store(
                $dueSubcription['total_price_per_payment'],
                $dueSubcription['total_price_per_payment'],
                0,
                $dueSubcription['payment_method_id'],
                $dueSubcription['currency'],
                null,
                $dueSubcription['subscription_id']
            );
        }

        $this->info('-----------------End Renewal Due Subscriptions command-----------------------');
    }
}