<?php

namespace Railroad\Ecommerce\Repositories;

use Illuminate\Http\Request;
use Railroad\Ecommerce\Composites\Query\ResultsQueryBuilderComposite;
use Railroad\Ecommerce\Entities\AccessCode;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
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
     * @return ResultsQueryBuilderComposite
     */
    public function searchByRequest(Request $request)
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
            )
            ->setParameter('term', '%' . $request->get('term') . '%');

        $results =
            $qb->getQuery()
                ->getResult();

        return new ResultsQueryBuilderComposite($results, $qb);
    }

    /**
     * @param Request $request
     *
     * @return ResultsQueryBuilderComposite
     */
    public function indexByRequest(Request $request)
    {
        $alias = 'a';

        $qb = $this->createQueryBuilder($alias);

        $qb->paginateByRequest($request)
            ->orderByRequest($request, $alias)
            ->restrictBrandsByRequest($request, $alias)
            ->select($alias);

        if (!empty($request->get('claimer_id'))) {
            $qb->andWhere(
                $qb->expr()
                    ->eq('a.claimer', ':claimerId')
            )
            ->setParameter('claimerId', $request->get('claimer_id'));
        }

        $results =
            $qb->getQuery()
                ->getResult();

        return new ResultsQueryBuilderComposite($results, $qb);
    }
}
