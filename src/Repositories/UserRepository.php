<?php

namespace Railroad\Ecommerce\Repositories;

use Illuminate\Http\Request;
use Railroad\Ecommerce\Composites\Query\ResultsQueryBuilderComposite;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\Traits\UseFormRequestQueryBuilder;

/**
 * Class UserRepository
 *
 * @method User find($id, $lockMode = null, $lockVersion = null)
 * @method User findOneBy(array $criteria, array $orderBy = null)
 * @method User[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method User[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class UserRepository extends RepositoryBase
{
    use UseFormRequestQueryBuilder;

    /**
     * RefundRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(User::class));
    }

    /**
     * @param Request $request
     *
     * @return ResultsQueryBuilderComposite
     */
    public function indexByRequest(Request $request): ResultsQueryBuilderComposite
    {
        $alias = 'u';

        $qb = $this->createQueryBuilder($alias);

        $qb->paginateByRequest($request)
            ->orderByRequest($request, $alias)
            ->select($alias);

        if ($request->has('term')) {
            $qb->andWhere(
                    $qb->expr()
                        ->like($alias . '.email', ':term')
                )
                ->setParameter('term', '%' . $request->get('term') . '%');
        }

        $results =
            $qb->getQuery()
                ->getResult();

        return new ResultsQueryBuilderComposite($results, $qb);
    }
}
