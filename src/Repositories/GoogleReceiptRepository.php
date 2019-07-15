<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\GoogleReceipt;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class GoogleReceiptRepository
 *
 * @method GoogleReceipt find($id, $lockMode = null, $lockVersion = null)
 * @method GoogleReceipt findOneBy(array $criteria, array $orderBy = null)
 * @method GoogleReceipt[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method GoogleReceipt[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class GoogleReceiptRepository extends EntityRepository
{
    /**
     * GoogleReceiptRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct(
            $em,
            $em->getClassMetadata(GoogleReceipt::class)
        );
    }
}
