<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\AccessCode;
use Railroad\Ecommerce\Entities\Product;

/**
 * Class ProductRepository
 *
 * @method Product find($id, $lockMode = null, $lockVersion = null)
 * @method Product findOneBy(array $criteria, array $orderBy = null)
 * @method Product[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Product[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class ProductRepository extends EntityRepository
{
    /**
     * ProductRepository constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(Product::class));
    }

    /**
     * Returns an array of Products from $accessCode productsIds
     *
     * @param AccessCode $accessCode
     *
     * @return array
     */
    public function getAccessCodeProducts(AccessCode $accessCode): array
    {
        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder();

        $qb
            ->select('p')
            ->from($this->getClassName(), 'p')
            ->where($qb->expr()->in('p.id', ':ids'));

        /**
         * @var $q \Doctrine\ORM\Query
         */
        $q = $qb->getQuery();

        $q->setParameter('ids', $accessCode->getProductIds());

        return $q->getResult();
    }

    /**
     * Returns an array of Products from $accessCodes productsIds
     *
     * @param array $accessCodes
     *
     * @return array
     */
    public function getAccessCodesProducts(array $accessCodes): array
    {
        $productIds = [];

        foreach ($accessCodes as $accessCode) {
            $accessCodeProductIds = array_flip($accessCode->getProductIds());

            $productIds += $accessCodeProductIds;
        }

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder();

        $qb
            ->select('p')
            ->from($this->getClassName(), 'p')
            ->where($qb->expr()->in('p.id', ':ids'));

        /**
         * @var $q \Doctrine\ORM\Query
         */
        $q = $qb->getQuery();

        $q->setParameter('ids', array_keys($productIds));

        return $q->getResult();
    }
}
