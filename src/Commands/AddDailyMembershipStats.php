<?php

namespace Railroad\Ecommerce\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Railroad\Ecommerce\Entities\MembershipStats;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Repositories\UserProductRepository;
use Throwable;

class AddDailyMembershipStats extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'AddDailyMembershipStats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create daily membership stats records';

    const LIFETIME_SKUS = [
        'PIANOTE-MEMBERSHIP-LIFETIME',
        'PIANOTE-MEMBERSHIP-LIFETIME-EXISTING-MEMBERS',
        'GUITAREO-LIFETIME-MEMBERSHIP',
        'DLM-Lifetime'
    ];

    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var Product[]
     */
    private $lifetimeProducts;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * @var UserProductRepository
     */
    private $userProductRepository;

    /**
     * AddDailyMembershipStats constructor.
     *
     * @param EcommerceEntityManager $entityManager
     * @param SubscriptionRepository $subscriptionRepository
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        ProductRepository $productRepository,
        SubscriptionRepository $subscriptionRepository,
        UserProductRepository $userProductRepository
    )
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->userProductRepository = $userProductRepository;
    }

    /**
     * Execute the console command.
     *
     * @throws Throwable
     */
    public function handle()
    {
        $this->info('Starting AddDailyMembershipStats.');

        $types = [
            MembershipStats::TYPE_ONE_MONTH => [
                'intervalType' => config('ecommerce.interval_type_monthly'),
                'intervalCount' => 1,
            ],
            MembershipStats::TYPE_SIX_MONTHS => [
                'intervalType' => config('ecommerce.interval_type_monthly'),
                'intervalCount' => 6,
            ],
            MembershipStats::TYPE_ONE_YEAR => [
                'intervalType' => config('ecommerce.interval_type_yearly'),
                'intervalCount' => 1,
            ],
        ];

        foreach ($types as $type => $subscriptionType) {
            $intervalType = $subscriptionType['intervalType'];
            $intervalCount = $subscriptionType['intervalCount'];

            $membershipStats = new MembershipStats();

            $membershipStats->setNew($this->getNewSubscriptions($intervalType, $intervalCount));
            $membershipStats->setActiveState($this->getActiveSubscriptions($intervalType, $intervalCount));
            $membershipStats->setExpired($this->getExpiredSubscriptions($intervalType, $intervalCount));
            $membershipStats->setSuspendedState($this->getSuspendedStateSubscriptions($intervalType, $intervalCount));
            $membershipStats->setCanceled($this->getCanceledSubscriptions($intervalType, $intervalCount));
            $membershipStats->setCanceledState($this->getCanceledStateSubscriptions($intervalType, $intervalCount));
            $membershipStats->setIntervalType($type);
            $membershipStats->setStatsDate(Carbon::yesterday());

            $this->entityManager->persist($membershipStats);
            $this->entityManager->flush();
        }

        $this->fetchLifetimeProducts();

        $membershipStats = new MembershipStats();

        $membershipStats->setNew($this->getNewLifetimeSubscriptions());
        $membershipStats->setActiveState($this->getActiveLifetimeSubscriptions());
        $membershipStats->setExpired(0);
        $membershipStats->setSuspendedState(0);
        $membershipStats->setCanceled(0);
        $membershipStats->setCanceledState(0);
        $membershipStats->setIntervalType(MembershipStats::TYPE_LIFETIME);
        $membershipStats->setStatsDate(Carbon::yesterday());

        $this->entityManager->persist($membershipStats);
        $this->entityManager->flush();

        $this->info('Finished AddDailyMembershipStats.');
    }

    public function getNewSubscriptions($intervalType, $intervalCount)
    {
        $qb = $this->subscriptionRepository->createQueryBuilder('s');

        $qb->select($qb->expr()->count('s.id'))
            ->where(
                $qb->expr()
                    ->eq('s.type', ':typeSubscription')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.intervalType', ':intervalType')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.intervalCount', ':intervalCount')
            )
            ->andWhere(
                $qb->expr()
                    ->between('s.createdAt', ':yesterdayStart', ':yesterdayEnd')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.isActive', ':active')
            )
            ->andWhere(
                $qb->expr()
                    ->isNull('s.canceledOn')
            );

        $q = $qb->getQuery();

        $q->setParameter('typeSubscription', Subscription::TYPE_SUBSCRIPTION);
        $q->setParameter('intervalType', $intervalType);
        $q->setParameter('intervalCount', $intervalCount);
        $q->setParameter('yesterdayStart', Carbon::yesterday());
        $q->setParameter('yesterdayEnd', Carbon::yesterday()->endOfDay());
        $q->setParameter('active', true);

        return $q->getSingleScalarResult();
    }

    public function getActiveSubscriptions($intervalType, $intervalCount)
    {
        $qb = $this->subscriptionRepository->createQueryBuilder('s');

        $qb->select($qb->expr()->count('s.id'))
            ->where(
                $qb->expr()
                    ->eq('s.type', ':typeSubscription')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.intervalType', ':intervalType')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.intervalCount', ':intervalCount')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.isActive', ':active')
            )
            ->andWhere(
                $qb->expr()
                    ->isNull('s.canceledOn')
            );

        $q = $qb->getQuery();

        $q->setParameter('typeSubscription', Subscription::TYPE_SUBSCRIPTION);
        $q->setParameter('intervalType', $intervalType);
        $q->setParameter('intervalCount', $intervalCount);
        $q->setParameter('active', true);

        return $q->getSingleScalarResult();
    }

    public function getExpiredSubscriptions($intervalType, $intervalCount)
    {
        $qb = $this->subscriptionRepository->createQueryBuilder('s');

        $qb->select($qb->expr()->count('s.id'))
            ->where(
                $qb->expr()
                    ->eq('s.type', ':typeSubscription')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.intervalType', ':intervalType')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.intervalCount', ':intervalCount')
            )
            ->andWhere(
                $qb->expr()
                    ->between('s.updatedAt', ':yesterdayStart', ':yesterdayEnd')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.isActive', ':suspended')
            )
            ->andWhere(
                $qb->expr()
                    ->isNull('s.canceledOn')
            );

        $q = $qb->getQuery();

        $q->setParameter('typeSubscription', Subscription::TYPE_SUBSCRIPTION);
        $q->setParameter('intervalType', $intervalType);
        $q->setParameter('intervalCount', $intervalCount);
        $q->setParameter('yesterdayStart', Carbon::yesterday());
        $q->setParameter('yesterdayEnd', Carbon::yesterday()->endOfDay());
        $q->setParameter('suspended', false);

        return $q->getSingleScalarResult();
    }

    public function getSuspendedStateSubscriptions($intervalType, $intervalCount)
    {
        $qb = $this->subscriptionRepository->createQueryBuilder('s');

        $qb->select($qb->expr()->count('s.id'))
            ->where(
                $qb->expr()
                    ->eq('s.type', ':typeSubscription')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.intervalType', ':intervalType')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.intervalCount', ':intervalCount')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.isActive', ':suspended')
            )
            ->andWhere(
                $qb->expr()
                    ->isNull('s.canceledOn')
            );

        $q = $qb->getQuery();

        $q->setParameter('typeSubscription', Subscription::TYPE_SUBSCRIPTION);
        $q->setParameter('intervalType', $intervalType);
        $q->setParameter('intervalCount', $intervalCount);
        $q->setParameter('suspended', false);

        return $q->getSingleScalarResult();
    }

    public function getCanceledSubscriptions($intervalType, $intervalCount)
    {
        $qb = $this->subscriptionRepository->createQueryBuilder('s');

        $qb->select($qb->expr()->count('s.id'))
            ->where(
                $qb->expr()
                    ->eq('s.type', ':typeSubscription')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.intervalType', ':intervalType')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.intervalCount', ':intervalCount')
            )
            ->andWhere(
                $qb->expr()
                    ->between('s.canceledOn', ':yesterdayStart', ':yesterdayEnd')
            );

        $q = $qb->getQuery();

        $q->setParameter('typeSubscription', Subscription::TYPE_SUBSCRIPTION);
        $q->setParameter('intervalType', $intervalType);
        $q->setParameter('intervalCount', $intervalCount);
        $q->setParameter('yesterdayStart', Carbon::yesterday());
        $q->setParameter('yesterdayEnd', Carbon::yesterday()->endOfDay());

        return $q->getSingleScalarResult();
    }

    public function getCanceledStateSubscriptions($intervalType, $intervalCount)
    {
        $qb = $this->subscriptionRepository->createQueryBuilder('s');

        $qb->select($qb->expr()->count('s.id'))
            ->where(
                $qb->expr()
                    ->eq('s.type', ':typeSubscription')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.intervalType', ':intervalType')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.intervalCount', ':intervalCount')
            )
            ->andWhere(
                $qb->expr()
                    ->isNotNull('s.canceledOn')
            );

        $q = $qb->getQuery();

        $q->setParameter('typeSubscription', Subscription::TYPE_SUBSCRIPTION);
        $q->setParameter('intervalType', $intervalType);
        $q->setParameter('intervalCount', $intervalCount);

        return $q->getSingleScalarResult();
    }

    public function fetchLifetimeProducts()
    {
        $qb = $this->productRepository->createQueryBuilder('p');

        $qb->where(
                $qb->expr()
                    ->in('p.sku', ':lifetimeSkus')
            );

        $q = $qb->getQuery();

        $q->setParameter('lifetimeSkus', self::LIFETIME_SKUS);

        $this->lifetimeProducts = $q->getResult();
    }

    public function getNewLifetimeSubscriptions()
    {
        $qb = $this->userProductRepository->createQueryBuilder('s');

        $qb->select($qb->expr()->count('s.id'))
            ->where(
                $qb->expr()
                    ->in('s.product', ':lifetimeProducts')
            )
            ->andWhere(
                $qb->expr()
                    ->between('s.createdAt', ':yesterdayStart', ':yesterdayEnd')
            )
            ->andWhere(
                $qb->expr()
                    ->isNull('s.deletedAt')
            );

        $q = $qb->getQuery();

        $q->setParameter('lifetimeProducts', $this->lifetimeProducts);
        $q->setParameter('yesterdayStart', Carbon::yesterday());
        $q->setParameter('yesterdayEnd', Carbon::yesterday()->endOfDay());

        return $q->getSingleScalarResult();
    }

    public function getActiveLifetimeSubscriptions()
    {
        $qb = $this->userProductRepository->createQueryBuilder('s');

        $qb->select($qb->expr()->count('s.id'))
            ->where(
                $qb->expr()
                    ->in('s.product', ':lifetimeProducts')
            )
            ->andWhere(
                $qb->expr()
                    ->isNull('s.deletedAt')
            );

        $q = $qb->getQuery();

        $q->setParameter('lifetimeProducts', $this->lifetimeProducts);

        return $q->getSingleScalarResult();
    }
}
