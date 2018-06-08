<?php

namespace Railroad\Ecommerce\Commands;

use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\ConfigService;

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
     * @var \Railroad\Ecommerce\Repositories\SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\PaymentRepository
     */
    private $paymentRepository;

    public function __construct(
        SubscriptionRepository $subscriptionRepository,
        PaymentRepository $paymentRepository
    ) {
        parent::__construct();
        $this->subscriptionRepository = $subscriptionRepository;
        $this->paymentRepository      = $paymentRepository;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('------------------Renewal Due Subscriptions command------------------');
        $dueSubscriptions = $this->subscriptionRepository->query()
            ->join(
                ConfigService::$tableSubscriptionPayment,
                ConfigService::$tableSubscription . '.id',
                '=',
                ConfigService::$tableSubscriptionPayment . '.subscription_id'
            )
            ->join(
                ConfigService::$tablePayment,
                ConfigService::$tableSubscriptionPayment . '.payment_id',
                '=',
                ConfigService::$tablePayment . '.id'
            )
            ->where('paid_until', '<=', Carbon::now()->toDateTimeString())
            ->where('is_active', '=', true)
            ->get()
            ->toArray();

        $this->info('Attempting to renew subscriptions. Count: ' . count($dueSubscriptions));
        $pay = [];
        foreach($dueSubscriptions as $dueSubcription)
        {

        }

        $this->info('-----------------End Renewal Due Subscriptions command-----------------------');
    }
}