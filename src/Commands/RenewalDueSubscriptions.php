<?php

namespace Railroad\Ecommerce\Commands;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\RenewalService;

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
     * @var RenewalService
     */
    private $renewalService;

    /**
     * RenewalDueSubscriptions constructor.
     *
     * @param SubscriptionRepository $subscriptionRepository
     * @param RenewalService $renewalService
     */
    public function __construct(
        SubscriptionRepository $subscriptionRepository,
        RenewalService $renewalService
    ) {
        parent::__construct();

        $this->subscriptionRepository = $subscriptionRepository;
        $this->renewalService = $renewalService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Railroad\Ecommerce\Exceptions\PaymentFailedException
     */
    public function handle()
    {
        $this->info('------------------Renewal Due Subscriptions command------------------');

        $dueSubscriptions =
            $this->subscriptionRepository->query()
                ->select(ConfigService::$tableSubscription . '.*')
                ->where('brand', ConfigService::$brand)
                ->where(
                    'paid_until',
                    '<',
                    Carbon::now()
                        ->toDateTimeString()
                )
                ->where(
                    'paid_until',
                    '>=',
                    Carbon::now()
                        ->subMonths(ConfigService::$subscriptionRenewalDateCutoff ?? 1)
                        ->toDateTimeString()
                )
                ->where('is_active', '=', true)
                ->whereNull('canceled_on')
                ->where(
                    function ($query) {
                        /** @var $query \Eloquent */
                        $query->whereNull(
                            'total_cycles_due'
                        )
                            ->orWhere(
                                'total_cycles_due',
                                0
                            )
                            ->orWhere('total_cycles_paid', '<', DB::raw('`total_cycles_due`'));
                    }
                )
                ->orderBy('start_date')
                ->get()
                ->toArray();

        $this->info('Attempting to renew subscriptions. Count: ' . count($dueSubscriptions));

        foreach ($dueSubscriptions as $dueSubscription) {
            $this->renewalService->renew($dueSubscription['id']); // todo: refactor to entity param
        }

        //deactivate ancient subscriptions
        $ancientSubscriptions =
            $this->subscriptionRepository->query()
                ->select(ConfigService::$tableSubscription . '.*')
                ->where('brand', ConfigService::$brand)
                ->where(
                    'paid_until',
                    '<',
                    Carbon::now()
                        ->subMonths(ConfigService::$subscriptionRenewalDateCutoff ?? 1)
                        ->toDateTimeString()
                )
                ->where('is_active', '=', true)
                ->whereNull('canceled_on')
                ->where(
                    function ($query) {
                        /** @var $query \Eloquent */
                        $query->whereNull(
                            'total_cycles_due'
                        )
                            ->orWhere(
                                'total_cycles_due',
                                0
                            )
                            ->orWhere('total_cycles_paid', '<', DB::raw('`total_cycles_due`'));
                    }
                )
                ->orderBy('start_date')
                ->get();

        $this->info('De-activate ancient subscriptions. Count: ' . count($ancientSubscriptions));

        $this->deactivateSubscriptions($ancientSubscriptions);

        $this->info('-----------------End Renewal Due Subscriptions command-----------------------');
    }

    /**
     * De-activate subscriptions and remove user's products.
     *
     * @param $ancientSubscriptions
     */
    private function deactivateSubscriptions($ancientSubscriptions)
    {
        $this->subscriptionRepository->query()
            ->whereIn('id', $ancientSubscriptions->pluck('id'))
            ->update(
                [
                    'is_active' => false,
                    'note' => 'Ancient subscription. De-activated.',
                    'updated_on' => Carbon::now()
                        ->toDateTimeString(),
                    'canceled_on' => Carbon::now()
                        ->toDateTimeString(),
                ]
            );
    }

}