<?php

namespace Railroad\Ecommerce\Commands;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\RenewalService;
use Railroad\Ecommerce\Services\UserProductService;

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
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var \Railroad\Ecommerce\Repositories\SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * @var RenewalService
     */
    private $renewalService;

    /**
     * @var UserProductService
     */
    private $userProductService;

    const DEACTIVATION_NOTE = 'Ancient subscription. De-activated.';

    /**
     * RenewalDueSubscriptions constructor.
     *
     * @param EcommerceEntityManager $entityManager
     * @param RenewalService $renewalService
     * @param UserProductService $userProductService
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        RenewalService $renewalService,
        UserProductService $userProductService
    ) {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->renewalService = $renewalService;
        $this->subscriptionRepository = $this->entityManager
                                        ->getRepository(Subscription::class);
        $this->userProductService = $userProductService;
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

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->subscriptionRepository->createQueryBuilder('s');

        $qb
            ->select(['s'])
            ->where($qb->expr()->eq('s.brand', ':brand'))
            ->andWhere($qb->expr()->lt('s.paidUntil', ':now'))
            ->andWhere($qb->expr()->gte('s.paidUntil', ':cutoff'))
            ->andWhere($qb->expr()->eq('s.isActive', ':active'))
            ->andWhere($qb->expr()->isNull('s.canceledOn'))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('s.totalCyclesDue'),
                    $qb->expr()->eq('s.totalCyclesDue', ':zero'),
                    $qb->expr()->lt('s.totalCyclesPaid', 's.totalCyclesDue')
                )
            )
            ->setParameter('brand', ConfigService::$brand)
            ->setParameter('now', Carbon::now())
            ->setParameter(
                'cutoff',
                Carbon::now()->subMonths(
                    ConfigService::$subscriptionRenewalDateCutoff ?? 1
                )
            )
            ->setParameter('active', true)
            ->setParameter('zero', 0);

        $dueSubscriptions = $qb->getQuery()->getResult();
        $this->info('Attempting to renew subscriptions. Count: ' . count($dueSubscriptions));

        foreach ($dueSubscriptions as $dueSubscription) {
            $this->renewalService->renew($dueSubscription);
        }

        // deactivate ancient subscriptions

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->subscriptionRepository->createQueryBuilder('s');

        $qb
            ->select(['s'])
            ->where($qb->expr()->eq('s.brand', ':brand'))
            ->andWhere($qb->expr()->lt('s.paidUntil', ':cutoff'))
            ->andWhere($qb->expr()->eq('s.isActive', ':active'))
            ->andWhere($qb->expr()->isNull('s.canceledOn'))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('s.totalCyclesDue'),
                    $qb->expr()->eq('s.totalCyclesDue', ':zero'),
                    $qb->expr()->lt('s.totalCyclesPaid', 's.totalCyclesDue')
                )
            )
            ->setParameter('brand', ConfigService::$brand)
            ->setParameter(
                'cutoff',
                Carbon::now()->subMonths(
                    ConfigService::$subscriptionRenewalDateCutoff ?? 1
                )
            )
            ->setParameter('active', true)
            ->setParameter('zero', 0);

        $ancientSubscriptions = $qb->getQuery()->getResult();

        $this->info('De-activate ancient subscriptions. Count: ' . count($ancientSubscriptions));

        foreach ($ancientSubscriptions as $ancientSubscription) {
            $ancientSubscription
                ->setIsActive(false)
                ->setNote(self::DEACTIVATION_NOTE)
                ->setCanceledOn(Carbon::now())
                ->setUpdatedAt(Carbon::now());

            $this->userProductService
                    ->updateSubscriptionProducts($ancientSubscription);
        }

        $this->entityManager->flush();

        $this->info('-----------------End Renewal Due Subscriptions command-----------------------');
    }
}