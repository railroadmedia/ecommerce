<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\NonUniqueResultException;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Composites\Query\ResultsQueryBuilderComposite;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Entities\UserProduct;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\Traits\UseFormRequestQueryBuilder;

/**
 * Class UserProductRepository
 *
 * @method UserProduct findOneBy(array $criteria, array $orderBy = null)
 * @method UserProduct[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method UserProduct[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class UserProductRepository extends RepositoryBase
{
    use UseFormRequestQueryBuilder {
        indexByRequest as baseIndexByRequest;
    }

    /**
     * CreditCardRepository constructor.
     *
     * @param EcommerceEntityManager $entityManager
     */
    public function __construct(EcommerceEntityManager $entityManager)
    {
        parent::__construct($entityManager, $entityManager->getClassMetadata(UserProduct::class));
    }

    /**
     * Finds an entity by its primary key / identifier.
     *
     * @param int $id The identifier.
     *
     * @return UserProduct
     *
     * @throws NonUniqueResultException
     */
    public function find(int $id)
    {
        $qb = $this->entityManager->createQueryBuilder();

        $q =
            $qb->from($this->entityName, 'a')
                ->select(['a', 'p'])
                ->join('a.product', 'p')
                ->where('a.id = :id')
                ->getQuery()
                ->setParameter('id', $id);

        return $q->getOneOrNullResult();
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
            ->select([$alias, 'p'])
            ->join('a.product', 'p')
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

    /**
     * @param Product[] $products
     *
     * @return UserProduct[]
     */
    public function getByProducts(array $products): array
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select('up')
            ->from(UserProduct::class, 'up')
            ->where(
                $qb->expr()
                    ->in('up.product', ':products')
            )
            ->setParameter('products', $products);

        return $qb->getQuery()->getResult();
    }
}
