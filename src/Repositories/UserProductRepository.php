<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Composites\Query\ResultsQueryBuilderComposite;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Entities\UserProduct;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\Traits\UseFormRequestQueryBuilder;

/**
 * Class UserProductRepository
 *
 * @method UserProduct find($id, $lockMode = null, $lockVersion = null)
 * @method UserProduct findOneBy(array $criteria, array $orderBy = null)
 * @method UserProduct[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method UserProduct[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class UserProductRepository extends EntityRepository
{
    use UseFormRequestQueryBuilder;

    /**
     * UserProductRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(UserProduct::class));
    }

    /**
     * @param Request $request
     * @param User $user
     * @return ResultsQueryBuilderComposite
     */
    public function indexByRequest(Request $request, User $user)
    {
        $alias = 'a';

        $qb = $this->createQueryBuilder($alias);

        $qb->paginateByRequest($request)
            ->orderByRequest($request, $alias)
            ->select($alias)
            ->andWhere(
                $qb->expr()
                    ->eq('a.user', ':user')
            )
            ->setParameter('user', $user);

        $results =
            $qb->getQuery()
                ->getResult();

        return new ResultsQueryBuilderComposite($results, $qb);
    }
}
