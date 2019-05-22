<?php

namespace Railroad\Ecommerce\Repositories;

use Carbon\Carbon;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Composites\Query\ResultsQueryBuilderComposite;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\Traits\UseFormRequestQueryBuilder;

/**
 * Class SubscriptionRepository
 *
 * @method Subscription find($id, $lockMode = null, $lockVersion = null)
 * @method Subscription findOneBy(array $criteria, array $orderBy = null)
 * @method Subscription[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Subscription[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class SubscriptionRepository extends RepositoryBase
{
    use UseFormRequestQueryBuilder;

    /**
     * @var UserProviderInterface
     */
    protected $userProvider;

    /**
     * SubscriptionRepository constructor.
     *
     * @param EcommerceEntityManager $em
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        EcommerceEntityManager $em,
        UserProviderInterface $userProvider
    ) {
        parent::__construct($em, $em->getClassMetadata(Subscription::class));

        $this->userProvider = $userProvider;
    }

    /**
     * @param $request
     *
     * @return ResultsQueryBuilderComposite
     */
    public function indexByRequest(Request $request): ResultsQueryBuilderComposite
    {
        $alias = 's';

        $qb = $this->createQueryBuilder($alias);

        $qb->paginateByRequest($request)
            ->orderByRequest($request, $alias)
            ->restrictBrandsByRequest($request, $alias)
            ->select(['s', 'p', 'o', 'pm'])
            ->leftJoin('s.product', 'p')
            ->leftJoin('s.order', 'o')
            ->leftJoin('s.paymentMethod', 'pm')
            ->andWhere(
                $qb->expr()
                    ->isNull('s' . '.deletedAt')
            );

        if ($request->has('user_id')) {

            $user = $this->userProvider->getUserById($request->get('user_id'));

            $qb->andWhere(
                $qb->expr()
                    ->eq('s' . '.user', ':user')
            )
                ->setParameter('user', $user);
        }

        $results =
            $qb->getQuery()
                ->getResult();

        return new ResultsQueryBuilderComposite($results, $qb);
    }

    /**
     * Gets subscriptions that are related to the specified products
     *
     * @param array $products - array of product entities
     *
     * @return array
     */
    public function getProductsSubscriptions(array $products): array
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select('s')
            ->from($this->getClassName(), 's')
            ->where(
                $qb->expr()
                    ->in('s.product', ':products')
            );

        /** @var $q Query */
        $q = $qb->getQuery();

        $q->setParameter('products', $products);

        return $q->getResult();
    }

    /**
     * Gets an order's subscription, based on specified product
     *
     * @param Order $order
     * @param Product $product
     *
     * @return Subscription|null
     *
     * @throws NonUniqueResultException
     */
    public function getOrderProductSubscription(
        Order $order,
        Product $product
    ): ?Subscription
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select('s')
            ->from($this->getClassName(), 's')
            ->where(
                $qb->expr()
                    ->eq('s.order', ':order')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.product', ':product')
            );

        /** @var $q Query */
        $q = $qb->getQuery();

        $q->setParameter('order', $order)
            ->setParameter('product', $product);

        return $q->getOneOrNullResult();
    }

    /**
     * @param $userId
     * @param array $productIds
     * @param bool $activeOnly - default false
     *
     * @return Subscription|null
     */
    public function getUserSubscriptionForProducts($userId, array $productIds, $activeOnly = false)
    {
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select('s')
            ->from($this->getClassName(), 's')
            ->where(
                $qb->expr()
                    ->eq('s.user', ':userId')
            )
            ->andWhere(
                $qb->expr()
                    ->in('IDENTITY(s.product)', ':productIds')
            );

        if ($activeOnly) {
            $qb->andWhere('s.isActive = true')
                ->andWhere(
                    $qb->expr()
                        ->gt('s.paidUntil', ':now')
                )
                ->andWhere(
                    $qb->expr()
                        ->isNull('s.canceledOn')
                )
                ->setParameter(
                    'now',
                    Carbon::now()
                        ->toDateTimeString()
                );
        }

        $subscriptions =
            $qb->setParameter('userId', $userId)
                ->setParameter('productIds', $productIds)
                ->orderBy('s.createdAt', 'desc')
                ->getQuery()
                ->getResult();

        if (count($subscriptions) > 1 && $activeOnly) {
            error_log(
                'User ' .
                $userId .
                ' has more than 1 active subscription for the given products ' .
                implode(',', $productIds)
            );
        }

        return $subscriptions[0] ?? null;
    }

    /**
     * @param $userId
     * @param array $limitToProductIds
     * @return Subscription[]
     */
    public function getAllUsersSubscriptions($userId, array $limitToProductIds = [])
    {
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select('s')
            ->from($this->getClassName(), 's')
            ->where(
                $qb->expr()
                    ->eq('s.user', ':userId')
            );

        if (!empty($limitToProductIds)) {
            $qb->andWhere(
                $qb->expr()
                    ->in('IDENTITY(s.product)', ':productIds')
            )
                ->setParameter('productIds', $limitToProductIds);
        }

        $subscriptions =
            $qb->setParameter('userId', $userId)
                ->orderBy('s.createdAt', 'desc')
                ->getQuery()
                ->getResult();

        return $subscriptions;
    }

    /**
     * @param Order $order
     *
     * @return Subscription[]
     */
    public function getOrderSubscriptions(Order $order): array
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select('s')
            ->from($this->getClassName(), 's')
            ->where(
                $qb->expr()
                    ->eq('s.order', ':order')
            )
            ->setParameter('order', $order);

        return $qb->getQuery()->getResult();
    }
}
