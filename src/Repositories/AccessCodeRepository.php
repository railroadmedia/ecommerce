<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\AccessCode;
use Railroad\Ecommerce\Entities\CreditCard;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\QueryBuilders\FromRequestEcommerceQueryBuilder;
use Railroad\Ecommerce\Repositories\Traits\UseFormRequestQueryBuilder;

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
class AccessCodeRepository extends RepositoryBase
{
    use UseFormRequestQueryBuilder;

    /**
     * CreditCardRepository constructor.
     *
     * @param EcommerceEntityManager $entityManager
     */
    public function __construct(EcommerceEntityManager $entityManager)
    {
        parent::__construct($entityManager, $entityManager->getClassMetadata(AccessCode::class));
    }

    /**
     * @param Request $request
     * @return AccessCode[]
     */
    public function searchByRequest(Request $request)
    {
        return $this->searchQueryBuilderByRequest($request)
            ->getQuery()
            ->setParameter('term', '%' . $request->get('term') . '%')
            ->getResult();
    }

    /**
     * @param Request $request
     * @return FromRequestEcommerceQueryBuilder
     */
    public function searchQueryBuilderByRequest(Request $request)
    {
        $alias = 'a';

        $qb = $this->createQueryBuilder($alias);

        $qb->paginateByRequest($request)
            ->orderByRequest($request, $alias)
            ->restrictBrandsByRequest($request, $alias)
            ->select($alias)
            ->andWhere(
                $qb->expr()
                    ->like($alias . '.code', ':term')
            );

        return $qb;
    }
}
