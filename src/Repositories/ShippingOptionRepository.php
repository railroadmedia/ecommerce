<?php

namespace Railroad\Ecommerce\Repositories;

use Illuminate\Http\Request;
use Railroad\Ecommerce\Composites\Query\ResultsQueryBuilderComposite;
use Railroad\Ecommerce\Entities\ShippingOption;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\Traits\UseFormRequestQueryBuilder;

/**
 * Class ShippingOptionRepository
 * @package Railroad\Ecommerce\Repositories
 */
class ShippingOptionRepository extends RepositoryBase
{
    use UseFormRequestQueryBuilder;

    /**
     * CreditCardRepository constructor.
     *
     * @param EcommerceEntityManager $entityManager
     */
    public function __construct(EcommerceEntityManager $entityManager)
    {
        parent::__construct($entityManager, $entityManager->getClassMetadata(ShippingOption::class));
    }

    /**
     * @param $request
     * @return ResultsQueryBuilderComposite
     */
    public function indexByRequest(Request $request): ResultsQueryBuilderComposite
    {
        $alias = 'so';

        $qb = $this->createQueryBuilder($alias);

        $qb->paginateByRequest($request)
            ->orderByRequest($request, $alias);

        $results =
            $qb->getQuery()
                ->getResult();

        return new ResultsQueryBuilderComposite($results, $qb);
    }

    /**
     * Get the first active shipping cost based on country and total weight
     *
     * @param string $country
     * @param float $totalWeight
     *
     * @return float
     */
    public function getShippingCosts(string $country, float $totalWeight): float
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select(['so', 'scwr'])
            ->from(ShippingOption::class, 'so')
            ->join('so.shippingCostsWeightRanges', 'scwr')
            ->andWhere(
                $qb->expr()
                    ->orX(
                        $qb->expr()
                            ->eq('so.country', ':country'),
                        $qb->expr()
                            ->eq('so.country', ':any')
                    )
            )
            ->andWhere(
                $qb->expr()
                    ->lte('scwr.min', ':totalWeight')
            )
            ->andWhere(
                $qb->expr()
                    ->gte('scwr.max', ':totalWeight')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('so.active', ':active')
            )
            ->setParameter('country', $country)
            ->setParameter('any', '*')
            ->setParameter('totalWeight', $totalWeight)
            ->setParameter('active', true)
            ->orderBy('so.priority', 'desc')
            ->addOrderBy('scwr.id');

        /**
         * @var $shippingOption ShippingOption
         */
        $shippingOption =
            $qb->getQuery()
                ->setQueryCacheDriver($this->arrayCache)
                ->getResult()[0] ?? null;

        if (!empty($shippingOption) && !empty($shippingOption->getShippingCostsWeightRanges()[0])) {
            return (float)$shippingOption->getShippingCostsWeightRanges()[0]->getPrice();
        }

        return 0.0;
    }

    /**
     * Get all the shipping costs weight ranges based on shipping option id
     *
     * @param int $shippingOptionId
     * @return mixed
     */
    public function getShippingCostsForShippingOption($shippingOptionId)
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select(['so', 'scwr'])
            ->from(ShippingOption::class, 'so')
            ->leftJoin('so.shippingCostsWeightRanges', 'scwr')
            ->where(
                $qb->expr()
                    ->eq('so.id', ':shippingOptionId')
            )
            ->setParameter('shippingOptionId', $shippingOptionId);

        return $qb->getQuery()
            ->setQueryCacheDriver($this->arrayCache)
            ->getResult();
    }
}
