<?php

namespace Railroad\Ecommerce\Repositories;

use Carbon\Carbon;
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
                        ->eq('s.subscriptionType', ':intervalType')
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

    /**
     * @param string $intervalType
     * @param int $intervalCount
     * @param Carbon|null $smallDateTime
     * @param Carbon|null $bigDateTime
     * @param string|null $brand
     *
     * @return array
     */
    public function getAverageMembershipEnd(
        string $intervalType,
        int $intervalCount,
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
                    ->in('s.type', ':types')
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
                    ->orX(
                        $qb->expr()
                            ->lte('s.paidUntil', ':paidUntilEnd'),
                        $qb->expr()
                            ->andX(
                                $qb->expr()
                                    ->isNotNull('s.canceledOn'),
                                $qb->expr()
                                    ->lte('s.canceledOn', ':canceledOnEnd')
                        )
                    )
            )
            ->setParameter(
                'types',
                [
                    Subscription::TYPE_SUBSCRIPTION,
                    Subscription::TYPE_APPLE_SUBSCRIPTION,
                    Subscription::TYPE_GOOGLE_SUBSCRIPTION,
                ]
            )
            ->setParameter('intervalType', $intervalType)
            ->setParameter('intervalCount', $intervalCount)
            ->setParameter('paidUntilEnd', $end)
            ->setParameter('canceledOnEnd', $end)
            ->groupBy('s.totalCyclesPaid');

        if ($smallDateTime) {
            $qb->andWhere(
                    $qb->expr()
                        ->gte('s.startDate', ':start')
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
