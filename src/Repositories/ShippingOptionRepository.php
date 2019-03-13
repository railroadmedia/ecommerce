<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\ShippingOption;

/**
 * Class ShippingOptionRepository
 *
 * @method ShippingOption find($id, $lockMode = null, $lockVersion = null)
 * @method ShippingOption findOneBy(array $criteria, array $orderBy = null)
 * @method ShippingOption[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method ShippingOption[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class ShippingOptionRepository extends EntityRepository
{
    /**
     * ShippingOptionRepository constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(ShippingOption::class));
    }

    /**
     * Get the first active shipping cost based on country and total weight
     *
     * @param string  $country
     * @param float $totalWeight
     *
     * @return mixed
     */
    public function getShippingCosts(string $country, float $totalWeight): ?array
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
