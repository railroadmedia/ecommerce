<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\Query\Expr\Join;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Composites\Query\ResultsQueryBuilderComposite;
use Railroad\Ecommerce\Entities\Customer;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\Traits\UseFormRequestQueryBuilder;

/**
 * Class CustomerRepository
 *
 * @method Customer find($id, $lockMode = null, $lockVersion = null)
 * @method Customer findOneBy(array $criteria, array $orderBy = null)
 * @method Customer[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Customer[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class CustomerRepository extends RepositoryBase
{
    use UseFormRequestQueryBuilder;

    /**
     * RefundRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(Customer::class));
    }

    /**
     * @param Request $request
     *
     * @return ResultsQueryBuilderComposite
     */
    public function indexByRequest(Request $request): ResultsQueryBuilderComposite
    {
        $alias = 'c';

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
