<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\AppleReceipt;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class AppleReceiptRepository
 *
 * @method AppleReceipt find($id, $lockMode = null, $lockVersion = null)
 * @method AppleReceipt findOneBy(array $criteria, array $orderBy = null)
 * @method AppleReceipt[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method AppleReceipt[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class AppleReceiptRepository extends EntityRepository
{
    /**
     * AppleReceiptRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct(
            $em,
            $em->getClassMetadata(AppleReceipt::class)
        );
    }
}
