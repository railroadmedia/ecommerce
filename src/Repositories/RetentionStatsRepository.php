<?php

namespace Railroad\Ecommerce\Repositories;

use Carbon\Carbon;
use Doctrine\ORM\Query\ResultSetMapping;
use Railroad\Ecommerce\Entities\RetentionStats;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class RetentionStatsRepository
 *
 * @method RetentionStats find($id, $lockMode = null, $lockVersion = null)
 * @method RetentionStats findOneBy(array $criteria, array $orderBy = null)
 * @method RetentionStats[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method RetentionStats[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class RetentionStatsRepository extends RepositoryBase
{
    /**
     * RetentionStatsRepository constructor.
     *
     * @param EcommerceEntityManager $entityManager
     */
    public function __construct(EcommerceEntityManager $entityManager)
    {
        parent::__construct($entityManager, $entityManager->getClassMetadata(RetentionStats::class));
    }

    /**
     * @param Carbon $smallDateTime
     * @param Carbon $bigDateTime
     * @param string|null $intervalType
     * @param string|null $brand
     *
     * @return array
     */
    public function getStats(
        Carbon $smallDateTime,
        Carbon $bigDateTime,
        ?string $intervalType = null,
        ?string $brand = null
    ): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('s')
            ->from(RetentionStats::class, 's')
            ->where(
                $qb->expr()
                    ->orX(
                        $qb->expr()
                            ->between('s.intervalStartDate', ':smallDateStart', ':bigDateStart'),
                        $qb->expr()
                            ->between('s.intervalEndDate', ':smallDateEnd', ':bigDateEnd')
                    )
            )
            ->orderBy('s.intervalStartDate', 'DESC')
            ->addOrderBy('s.intervalEndDate', 'DESC')
            ->setParameter('smallDateStart', $smallDateTime->toDateString())
            ->setParameter('smallDateEnd', $smallDateTime->toDateString())
            ->setParameter('bigDateStart', $bigDateTime->toDateString())
            ->setParameter('bigDateEnd', $bigDateTime->toDateString());

        if ($intervalType) {
            $qb->andWhere(
                    $qb->expr()
                        ->eq('s.intervalType', ':intervalType')
                )
                ->setParameter('intervalType', $intervalType);
        }

        if ($brand) {
            $qb->andWhere(
                    $qb->expr()
                        ->eq('s.brand', ':brand')
                )
                ->setParameter('brand', $brand);
        }

        return $qb->getQuery()
                    ->getResult();
    }

    public function getAverageStatsOneMonth(
        ?Carbon $smallDateTime,
        ?Carbon $bigDateTime,
        ?string $brand = null
    ): array
    {
        $end = $bigDateTime ?: Carbon::now()->endOfDay();

        $qb = $this->entityManager->createQueryBuilder();

        $qb->select(['s.brand', 's.totalCyclesPaid', 'COUNT(s.id) AS count'])
            ->from(Subscription::class, 's')
            ->where(
                $qb->expr()
                    ->eq('s.intervalType', ':month')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.intervalCount', ':one')
            )
            ->andWhere(
                $qb->expr()
                    ->orX(
                        $qb->expr()
                            ->lte('s.paidUntil', ':end'),
                        $qb->expr()
                            ->isNotNull('s.canceledOn')
                    )
            )
            ->setParameter('month', config('ecommerce.interval_type_monthly'))
            ->setParameter('one', 1)
            ->setParameter('end', $end)
            ->groupBy('s.totalCyclesPaid');

        if ($smallDateTime) {
            $qb->andWhere(
                    $qb->expr()
                        ->gte('s.startSate', ':start')
                )
                ->setParameter('start', $smallDateTime);
        }

        if ($brand) {
            $qb->andWhere(
                    $qb->expr()
                        ->eq('s.brand', ':brand')
                )
                ->setParameter('brand', $brand);
        } else {
            $qb->addGroupBy('s.brand');
        }

        return $qb->getQuery()
                    ->getResult();
    }

    public function getAverageStatsSixMonths(
        ?Carbon $smallDateTime,
        ?Carbon $bigDateTime,
        ?string $brand = null
    ): array
    {
        $end = $bigDateTime ?: Carbon::now()->endOfDay();

        $qb = $this->entityManager->createQueryBuilder();

        $qb->select(['s.brand', 's.totalCyclesPaid', 'COUNT(s.id) AS count'])
            ->from(Subscription::class, 's')
            ->where(
                $qb->expr()
                    ->eq('s.intervalType', ':month')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.intervalCount', ':six')
            )
            ->andWhere(
                $qb->expr()
                    ->orX(
                        $qb->expr()
                            ->lte('s.paidUntil', ':end'),
                        $qb->expr()
                            ->isNotNull('s.canceledOn')
                    )
            )
            ->setParameter('month', config('ecommerce.interval_type_monthly'))
            ->setParameter('six', 6)
            ->setParameter('end', $end)
            ->groupBy('s.totalCyclesPaid');

        if ($smallDateTime) {
            $qb->andWhere(
                    $qb->expr()
                        ->gte('s.startSate', ':start')
                )
                ->setParameter('start', $smallDateTime);
        }

        if ($brand) {
            $qb->andWhere(
                    $qb->expr()
                        ->eq('s.brand', ':brand')
                )
                ->setParameter('brand', $brand);
        } else {
            $qb->addGroupBy('s.brand');
        }

        return $qb->getQuery()
                    ->getResult();
    }

    public function getAverageStatsOneYear(
        ?Carbon $smallDateTime,
        ?Carbon $bigDateTime,
        ?string $brand = null
    ): array
    {
        $end = $bigDateTime ?: Carbon::now()->endOfDay();

        $qb = $this->entityManager->createQueryBuilder();

        $qb->select(['s.brand', 's.totalCyclesPaid', 'COUNT(s.id) AS count'])
            ->from(Subscription::class, 's')
            ->where(
                $qb->expr()
                    ->eq('s.intervalType', ':year')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.intervalCount', ':one')
            )
            ->andWhere(
                $qb->expr()
                    ->orX(
                        $qb->expr()
                            ->lte('s.paidUntil', ':end'),
                        $qb->expr()
                            ->isNotNull('s.canceledOn')
                    )
            )
            ->setParameter('year', config('ecommerce.interval_type_yearly'))
            ->setParameter('one', 1)
            ->setParameter('end', $end)
            ->groupBy('s.totalCyclesPaid');

        if ($smallDateTime) {
            $qb->andWhere(
                    $qb->expr()
                        ->gte('s.startSate', ':start')
                )
                ->setParameter('start', $smallDateTime);
        }

        if ($brand) {
            $qb->andWhere(
                    $qb->expr()
                        ->eq('s.brand', ':brand')
                )
                ->setParameter('brand', $brand);
        } else {
            $qb->addGroupBy('s.brand');
        }

        return $qb->getQuery()
                    ->getResult();
    }
}
