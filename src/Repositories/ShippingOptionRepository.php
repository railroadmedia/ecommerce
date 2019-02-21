<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;

class ShippingOptionRepository extends EntityRepository
{
    /**
     * Determines whether inactive shipping options will be pulled or not.
     *
     * @var array|bool
     */
    public static $pullInactiveShippingOptions = true;

    /*
    // todo - review and clean
    protected function decorate($results)
    {
        return Decorator::decorate($results, 'shippingOptions');
    }
    */

    /**
     * Get the first active shipping cost based on country and total weight
     *
     * @param string  $country
     * @param integer $totalWeight
     *
     * @return mixed
     */
    public function getShippingCosts(string $country, float $totalWeight)
    {
        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder();

        $qb
            ->select(['so', 'scwr'])
            ->from($this->getClassName(), 'so')
            ->join('so.shippingCostsWeightRanges', 'scwr')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('so.country', ':country'),
                    $qb->expr()->eq('so.country', ':any')
                )
            )
            ->andWhere($qb->expr()->lte('scwr.min', ':totalWeight'))
            ->andWhere($qb->expr()->gte('scwr.max', ':totalWeight'))
            ->setParameter('country', $country)
            ->setParameter('any', '*')
            ->setParameter('totalWeight', $totalWeight);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all the shipping costs weight ranges based on shipping option id
     *
     * @param int $shippingOptionId
     * @return mixed
     */
    public function getShippingCostsForShippingOption($shippingOptionId)
    {
        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder();

        $qb
            ->select(['so', 'scwr'])
            ->from($this->getClassName(), 'so')
            ->leftJoin('so.shippingCostsWeightRanges', 'scwr')
            ->where($qb->expr()->eq('so.id', ':shippingOptionId'))
            ->setParameter('shippingOptionId', $shippingOptionId);

        return $qb->getQuery()->getResult();
    }
}
