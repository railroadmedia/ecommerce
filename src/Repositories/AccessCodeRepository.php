<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\AccessCode;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\QueryBuilders\FromRequestEcommerceQueryBuilder;

/**
 * Class AccessCodeRepository
 *
 * @method AccessCode find($id, $lockMode = null, $lockVersion = null)
 * @method AccessCode findOneBy(array $criteria, array $orderBy = null)
 * @method AccessCode[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method AccessCode[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class AccessCodeRepository extends EntityRepository
{
    /**
     * AccessCodeRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(AccessCode::class));
    }

    /**
     * @param string $alias
     * @param null $indexBy
     * @return FromRequestEcommerceQueryBuilder
     */
    public function createQueryBuilder($alias, $indexBy = null)
    {
        $queryBuilder = new FromRequestEcommerceQueryBuilder($this->_em);

        $queryBuilder->select($alias)
            ->from($this->_entityName, $alias, $indexBy);

        return $queryBuilder;
    }
}
