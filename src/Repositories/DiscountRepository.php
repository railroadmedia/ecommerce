<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\ORMException;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Composites\Query\ResultsQueryBuilderComposite;
use Railroad\Ecommerce\Entities\Discount;
use Railroad\Ecommerce\Entities\DiscountCriteria;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\Traits\UseFormRequestQueryBuilder;
use Railroad\Ecommerce\Services\DiscountCriteriaService;
use Railroad\Ecommerce\Services\DiscountService;
use Railroad\Ecommerce\Services\UpgradeService;

/**
 * Class DiscountRepository
 *
 * @package Railroad\Ecommerce\Repositories
 */
class DiscountRepository extends RepositoryBase
{
    use UseFormRequestQueryBuilder;

    /**
     * CreditCardRepository constructor.
     *
     * @param EcommerceEntityManager $entityManager
     */
    public function __construct(EcommerceEntityManager $entityManager)
    {
        parent::__construct($entityManager, $entityManager->getClassMetadata(Discount::class));
    }

    /**
     * @param $request
     * @return ResultsQueryBuilderComposite
     */
    public function indexByRequest(Request $request)
    {
        $alias = 'd';
        $aliasProduct = 'p';

        $qb = $this->createQueryBuilder($alias);

        $qb->paginateByRequest($request)
            ->orderByRequest($request, $alias)
            ->select([$alias, $aliasProduct])
            ->leftJoin($alias . '.product', $aliasProduct)
            ->andWhere(
                $qb->expr()
                    ->orX(
                        $qb->expr()
                            ->isNull($alias . '.expirationDate'),
                        $qb->expr()
                            ->gte($alias . '.expirationDate', 'CURRENT_TIMESTAMP()')
                    )
            );

        $results =
            $qb->getQuery()
                ->getResult();

        return new ResultsQueryBuilderComposite($results, $qb);
    }

    /**
     * Returns Discount with specified id
     *
     * @param int $id
     * @return Discount
     * @throws ORMException
     */
    public function find(int $id): ?Discount
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select(['d', 'p', 'dc'])
            ->from(Discount::class, 'd')
            ->leftJoin('d.product', 'p')
            ->leftJoin('d.discountCriterias', 'dc')
            ->leftJoin('dc.products', 'dcp')
            ->andWhere(
                $qb->expr()
                    ->eq('d.id', ':id')
            )
            ->setParameter('id', $id);

        return $qb->getQuery()
            ->setResultCacheDriver($this->arrayCache)
            ->setQueryCacheDriver($this->arrayCache)
            ->getOneOrNullResult();
    }

    /**
     * Returns an array of Discounts with associated DiscountCriteria loaded
     *
     * @return Discount[]
     * @throws ORMException
     */
    public function getActiveDiscounts()
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select(['d', 'dc', 'p', 'dcp'])
            ->from(Discount::class, 'd')
            ->leftJoin('d.product', 'p')
            ->leftJoin('d.discountCriterias', 'dc')
            ->leftJoin('dc.products', 'dcp')
            ->where(
                $qb->expr()
                    ->eq('d.active', ':active')
            )
            ->andWhere(
                $qb->expr()
                    ->orX(
                        $qb->expr()
                            ->isNull('d.expirationDate'),
                        $qb->expr()
                            ->gte('d.expirationDate', 'CURRENT_TIMESTAMP()')
                    )
            )
            ->setParameter('active', true);

        $results = $qb->getQuery()
            ->getResult();

        $upgradeDiscount = new Discount();
        $upgradeDiscount->setType(DiscountService::MEMBERSHIP_UPGRADE_TYPE);
        foreach (UpgradeService::FullTierSKUs as $upgradeSKU) {
            $discountCriteria = new DiscountCriteria();
            $discountCriteria->setType(DiscountCriteriaService::IS_MEMBERSHIP_UPGRADE);
            $upgradeDiscount->addDiscountCriteria($discountCriteria);
        }

        $results[] = $upgradeDiscount;
        return $results;
    }

    /**
     * Returns an array of Discounts with associated DiscountCriteria loaded
     *
     * @return Discount[]
     * @throws ORMException
     */
    public function getActiveShippingDiscounts()
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select(['d', 'dc', 'p'])
            ->from(Discount::class, 'd')
            ->leftJoin('d.discountCriterias', 'dc')
            ->leftJoin('d.product', 'p')
            ->leftJoin('dc.products', 'dcp')
            ->where(
                $qb->expr()
                    ->eq('d.active', ':active')
            )
            ->andWhere(
                $qb->expr()
                    ->in('d.type', ':types')
            )
            ->andWhere(
                $qb->expr()
                    ->orX(
                        $qb->expr()
                            ->isNull('d.expirationDate'),
                        $qb->expr()
                            ->gte('d.expirationDate', 'CURRENT_TIMESTAMP()')
                    )
            )
            ->setParameter('active', true)
            ->setParameter(
                'types',
                [
                    DiscountService::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE,
                    DiscountService::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE,
                    DiscountService::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE
                ]
            );

        return $qb->getQuery()
            ->setResultCacheDriver($this->arrayCache)
            ->setQueryCacheDriver($this->arrayCache)
            ->getResult();
    }

    /**
     * Returns an array of Discounts with associated DiscountCriteria loaded
     *
     * @return Discount[]
     * @throws ORMException
     */
    public function getActiveCartItemDiscounts()
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select(['d', 'dc', 'p'])
            ->from(Discount::class, 'd')
            ->leftJoin('d.product', 'p')
            ->leftJoin('d.discountCriterias', 'dc')
            ->leftJoin('dc.products', 'dcp')
            ->where(
                $qb->expr()
                    ->eq('d.active', ':active')
            )
            ->andWhere(
                $qb->expr()
                    ->in('d.type', ':types')
            )
            ->andWhere(
                $qb->expr()
                    ->orX(
                        $qb->expr()
                            ->isNull('d.expirationDate'),
                        $qb->expr()
                            ->gte('d.expirationDate', 'CURRENT_TIMESTAMP()')
                    )
            )
            ->setParameter('active', true)
            ->setParameter(
                'types',
                [
                    DiscountService::PRODUCT_AMOUNT_OFF_TYPE,
                    DiscountService::PRODUCT_PERCENT_OFF_TYPE,
                    DiscountService::SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE,
                    DiscountService::SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE
                ]
            );

        return $qb->getQuery()
            ->setResultCacheDriver($this->arrayCache)
            ->setQueryCacheDriver($this->arrayCache)
            ->getResult();
    }
}
