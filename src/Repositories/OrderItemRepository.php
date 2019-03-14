<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
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
class OrderItemRepository extends EntityRepository
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
}
