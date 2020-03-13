<?php

namespace Railroad\Ecommerce\Repositories;

use Carbon\Carbon;
use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\RetentionStats;
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
class RetentionStatsRepository extends EntityRepository
{
    /**
     * RetentionStatsRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct(
            $em,
            $em->getClassMetadata(RetentionStats::class)
        );
    }

    public function getStats(
        Carbon $smallDateTime,
        Carbon $bigDateTime,
        ?string $intervalType = null,
        ?string $brand = null
    ) {
        $qb = $this->createQueryBuilder('s');

        $qb->where(
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
}
