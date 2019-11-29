<?php

namespace Railroad\Ecommerce\Repositories;

use Carbon\Carbon;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Composites\Query\ResultsQueryBuilderComposite;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\Traits\UseFormRequestQueryBuilder;

/**
 * Class OrderRepository
 *
 * @method Order find($id, $lockMode = null, $lockVersion = null)
 * @method Order findOneBy(array $criteria, array $orderBy = null)
 * @method Order[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Order[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class OrderRepository extends RepositoryBase
{
    use UseFormRequestQueryBuilder;

    /**
     * OrderRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(Order::class));
    }

    /**
     * Returns true if any order has billing or shipping $address set
     * The usage of select count() avoids NonUniqueResultException exception
     *
     * @param Address $address
     *
     * @return bool
     *
     * @throws NonUniqueResultException
     */
    public function ordersWithAdressExist(Address $address): bool
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select('COUNT(o)')
            ->from($this->getClassName(), 'o')
            ->where(
                $qb->expr()
                    ->eq('o.shippingAddress', ':address')
            )
            ->orWhere(
                $qb->expr()
                    ->eq('o.billingAddress', ':address')
            );

        /** @var $q Query */
        $q = $qb->getQuery();

        $q->setParameter('address', $address);

        return (integer)$q->getSingleScalarResult() > 0;
    }

    /**
     * @param Request $request
     *
     * @return ResultsQueryBuilderComposite
     */
    public function indexByRequest(Request $request): ResultsQueryBuilderComposite
    {
        $alias = 'o';

        $qb = $this->createQueryBuilder($alias);

        $qb->paginateByRequest($request)
            ->restrictSoftDeleted($request, $alias)
            ->orderByRequest($request, $alias)
            ->select(['o', 'oi', 'ba', 'sa', 'p'])
            ->leftJoin('o.orderItems', 'oi')
            ->leftJoin('oi.product', 'p')
            ->leftJoin('o.billingAddress', 'ba')
            ->leftJoin('o.shippingAddress', 'sa');

        if ($request->has('start-date')) {
            $startDate = Carbon::parse($request->get('start-date', Carbon::now()->subMonth()->toDateTimeString()));
        }

        if ($request->has('end-date')) {
            $endDate = Carbon::parse($request->get('end-date', Carbon::now()->toDateTimeString()));
        }

        if (isset($startDate) && isset($endDate)) {
            $qb->andWhere(
                    $qb->expr()
                        ->between('o.createdAt', ':startDate', ':endDate')
                )
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }

        if ($request->has('user_id')) {
            $qb->andWhere('o.user = :userId')
                ->setParameter('userId', $request->get('user_id'));
        }

        if ($request->has('customer_id')) {
            $qb->andWhere('IDENTITY(o.customer) = :customerId')
                ->setParameter('customerId', $request->get('customer_id'));
        }

        $results =
            $qb->getQuery()
                ->getResult();

        return new ResultsQueryBuilderComposite($results, $qb);
    }

    /**
     * @param int $orderId
     *
     * @return Order
     *
     * @throws NonUniqueResultException
     */
    public function getDecoratedOrder(int $orderId): Order
    {
        /**
         * @var $qb QueryBuilder
         */
        $qb = $this->createQueryBuilder('o');

        $qb->select(['o', 'oi', 'ba', 'sa', 'p'])
            ->leftJoin('o.orderItems', 'oi')
            ->leftJoin('oi.product', 'p')
            ->leftJoin('o.billingAddress', 'ba')
            ->leftJoin('o.shippingAddress', 'sa')
            ->where(
                $qb->expr()
                    ->in('o.id', ':orderId')
            )
            ->setParameter('orderId', $orderId);

        return
            $qb->getQuery()
                ->getOneOrNullResult();
    }
}
