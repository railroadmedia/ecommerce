<?php

namespace Railroad\Ecommerce\Repositories;

use Carbon\Carbon;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Composites\Query\ResultsQueryBuilderComposite;
use Railroad\Ecommerce\Entities\DiscountCriteria;
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
            ->restrictSoftDeleted($request, $alias)
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
     * @param $userId
     * @return UserProduct[]
     */
    public function getAllUsersProducts($userId)
    {
        $alias = 'a';

        $qb = $this->createQueryBuilder($alias);

        $qb->select([$alias, 'p'])
            ->join('a.product', 'p')
            ->andWhere(
                $qb->expr()
                    ->eq('a.user', ':userId')
            )
            ->setParameter('userId', $userId);

        return $qb->getQuery()
            ->getResult();
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

    /**
     * @param User $user
     * @param DiscountCriteria $discountCriteria
     *
     * @param null $maxOverride
     * @return int
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getCountByUserDiscountCriteriaProducts(
        User $user,
        DiscountCriteria $discountCriteria,
        $maxOverride = null
    ): int {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select('COUNT(up)')
            ->from(UserProduct::class, 'up')
            ->where(
                $qb->expr()
                    ->eq('up.user', ':user')
            )
            ->andWhere(
                $qb->expr()
                    ->in('up.product', ':products')
            )
            ->andWhere(
                $qb->expr()
                    ->orX(
                        $qb->expr()
                            ->gte('up.expirationDate', 'CURRENT_TIMESTAMP()'),
                        $qb->expr()
                            ->isNull('up.expirationDate')
                    )
            )
            ->andWhere(
                $qb->expr()
                    ->between('up.quantity', ':min', ':max')
            )
            ->setParameter('user', $user)
            ->setParameter('products', $discountCriteria->getProducts())
            ->setParameter('min', (integer)$discountCriteria->getMin())
            ->setParameter('max', !empty($maxOverride) ? $maxOverride : (integer)$discountCriteria->getMax());

        return (integer)$qb->getQuery()
            ->getSingleScalarResult();
    }

    public function getLatestExpirationDateByBrand(User $user, string $brand): ?Carbon
    {
        /** @var $qb QueryBuilder */
        $qb = $this->getEntityManager()->createQueryBuilder('up');

        $qb->select('max(up.expirationDate) as expirationDate')
            ->from(UserProduct::class, 'up')
            ->join('up.product', 'p')
            ->where($qb->expr()->eq('up.user', ':user'))
            ->andWhere($qb->expr()->eq('p.brand', ':brand'))
            ->orderBy('up.expirationDate', 'desc')
            ->setParameter('user', $user)
            ->setParameter('brand', $brand);;
        $result = $qb->getQuery()->getOneOrNullResult()['expirationDate'];
        return $result ? Carbon::parse($result) : null;
    }
}
