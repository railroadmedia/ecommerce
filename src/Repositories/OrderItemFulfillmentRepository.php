<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\QueryBuilder;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Composites\Query\ResultsQueryBuilderComposite;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\OrderItemFulfillment;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\Traits\UseFormRequestQueryBuilder;

/**
 * Class OrderItemFulfillmentRepository
 *
 * @method OrderItemFulfillment find($id, $lockMode = null, $lockVersion = null)
 * @method OrderItemFulfillment findOneBy(array $criteria, array $orderBy = null)
 * @method OrderItemFulfillment[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method OrderItemFulfillment[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class OrderItemFulfillmentRepository extends RepositoryBase
{
    use UseFormRequestQueryBuilder;

    /**
     * OrderItemFulfillmentRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct(
            $em,
            $em->getClassMetadata(OrderItemFulfillment::class)
        );
    }

    /**
     * @param $request
     *
     * @return ResultsQueryBuilderComposite
     */
    public function indexByRequest(Request $request): ResultsQueryBuilderComposite
    {
        $statuses = (array)$request->get(
            'status',
            [
                config('ecommerce.fulfillment_status_pending'),
                config('ecommerce.fulfillment_status_fulfilled')
            ]
        );

        $qb = $this->createQueryBuilder('oif');

        $qb->orderByRequest($request, 'oif')
            ->restrictBetweenTimes($request, 'oif')
            ->select(['oif', 'o', 'oi', 'oip'])
            ->join('oif.order', 'o')
            ->join('oif.orderItem', 'oi')
            ->join('oi.product', 'oip')
            ->andWhere(
                $qb->expr()
                    ->in('oif.status', ':statuses')
            )
            ->setParameter('statuses', $statuses);

        $results =
            $qb->getQuery()
                ->getResult();

        return new ResultsQueryBuilderComposite($results, $qb);
    }

    /**
     * @param int $orderId
     * @param int $orderItemId - optional
     *
     * @return OrderItemFulfillment[]
     */
    public function getByOrderAndOrderItem(int $orderId, ?int $orderItemId = null): array
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select('oif')
            ->from(OrderItemFulfillment::class, 'oif')
            ->where(
                $qb->expr()
                    ->eq('IDENTITY(oif.order)', ':orderId')
            )
            ->setParameter('orderId', $orderId);

        if ($orderItemId) {
            $qb->andWhere(
                $qb->expr()
                    ->eq('IDENTITY(oif.orderItem)', ':orderItemId')
            )
                ->setParameter('orderItemId', $orderItemId);
        }

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @param Order[] $orders
     *
     * @return OrderItemFulfillment[]
     */
    public function getByOrders(array $orders): array
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select('oif')
            ->from(OrderItemFulfillment::class, 'oif')
            ->where(
                $qb->expr()
                    ->in('oif.order', ':orders')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('oif.status', ':status')
            )
            ->andWhere(
                $qb->expr()
                    ->isNull('oif.fulfilledOn')
            )
            ->setParameter('orders', $orders)
            ->setParameter('status', config('ecommerce.fulfillment_status_pending'));

        return $qb->getQuery()
            ->getResult();
    }
}
