<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\ActionLog;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class ActionLogRepository
 *
 * @method ActionLog find($id, $lockMode = null, $lockVersion = null)
 * @method ActionLog findOneBy(array $criteria, array $orderBy = null)
 * @method ActionLog[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method ActionLog[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class ActionLogRepository extends EntityRepository
{
    /**
     * ActionLogRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct(
            $em,
            $em->getClassMetadata(ActionLog::class)
        );
    }
}
