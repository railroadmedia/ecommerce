<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\QueryBuilder;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\OrderItem;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class OrderItemRepository
 *
 * @method OrderItem find($id, $lockMode = null, $lockVersion = null)
 * @method OrderItem findOneBy(array $criteria, array $orderBy = null)
 * @method OrderItem[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method OrderItem[] findByProduct(Product $product)
 * @method OrderItem[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class OrderItemRepository extends RepositoryBase
{
    /**
     * OrderItemRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(OrderItem::class));
    }

    /**
     * @param Order[] $orders
     *
     * @return OrderItem[]
     */
    public function getByOrders(array $orders): array
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select(['oi', 'p'])
            ->from(OrderItem::class, 'oi')
            ->join('oi.product', 'p')
            ->where(
                $qb->expr()
                    ->in('oi.order', ':orders')
            )
            ->setParameter('orders', $orders);

        return $qb->getQuery()->getResult();
    }


    /**
     * Check and return the price paid by a user for a certain product. An array is returned instead of a singleScalarResult.
     * In case of no result or more than one rows returned, the discount will not be applied
     *
     * @param int $productId
     * @param int $userId
     *
     * @return array
     */
    public function getFinalPriceByProductAndUser(int $productId, int $userId): array
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();
        $qb->select(['oi.finalPrice'])
            ->from(OrderItem::class, 'oi')
            ->join('oi.order', 'o')
            ->where(
                $qb->expr()
                    ->eq('oi.product', ':productId')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('o.user', ':userId')
            )
            ->setParameter('productId', $productId)
            ->setParameter('userId', $userId);

        return $qb->getQuery()->getResult();
    }
}
