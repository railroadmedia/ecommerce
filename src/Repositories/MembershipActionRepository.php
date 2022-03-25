<?php

namespace Railroad\Ecommerce\Repositories;

use Illuminate\Http\Request;
use Railroad\Ecommerce\Composites\Query\ResultsQueryBuilderComposite;
use Railroad\Ecommerce\Entities\MembershipAction;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\Traits\UseFormRequestQueryBuilder;

/**
 * Class MembershipActionRepository
 *
 * @method MembershipAction find($id, $lockMode = null, $lockVersion = null)
 * @method MembershipAction findOneBy(array $criteria, array $orderBy = null)
 * @method MembershipAction[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method MembershipAction[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class MembershipActionRepository extends RepositoryBase
{
    use UseFormRequestQueryBuilder;

    /**
     * RefundRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(MembershipAction::class));
    }

    /**
     * @param Request $request
     *
     * @return ResultsQueryBuilderComposite
     */
    public function indexByRequest(Request $request): ResultsQueryBuilderComposite
    {
        $alias = 'm';

        $qb = $this->createQueryBuilder($alias);

        $qb->select(['m', 's'])
            ->paginateByRequest($request)
            ->orderByRequest($request, $alias)
            ->restrictBrandsByRequest($request, $alias)
            ->leftJoin('m.subscription', 's');

        if ($request->has('user_id')) {
            $qb->andWhere(
                $qb->expr()
                    ->eq($alias . '.user', ':userId')
            )
                ->setParameter('userId', $request->get('user_id'));
        }

        if ($request->has('subscription_id')) {
            $qb->andWhere(
                $qb->expr()
                    ->eq($alias . '.subscription', ':subscriptionId')
            )
                ->setParameter('subscriptionId', $request->get('subscription_id'));
        }

        if ($request->has('brand')) {
            $qb->andWhere(
                $qb->expr()
                    ->eq($alias . '.brand', ':brand')
            )
                ->setParameter('brand', $request->get('brand'));
        }

        $results = $qb->getQuery()
            ->getResult();

        return new ResultsQueryBuilderComposite($results, $qb);
    }


    /**
     * @param string $userId
     * @param string|null $brand // looks at all brands if null
     * @return MembershipAction|null
     */
    public function getUsersLatestMembershipAction($userId, $brand = null)
    {
        return $this->getAllUsersMembershipActions($userId, $brand)[0] ?? null;
    }

    /**
     * @param integer $userId
     * @param string|null $brand // gets for all brands if null
     * @param string $orderByAttribute
     * @param string $orderByDirection
     *
     * @return MembershipAction[]
     */
    public function getAllUsersMembershipActions(
        $userId,
        $brand = null,
        $orderByAttribute = 'createdAt',
        $orderByDirection = 'desc'
    )
    {
        $qb = $this->createQueryBuilder('m');

        $qb->select(['m'])
            ->where(
                $qb->expr()
                    ->eq('m.user', ':userId')
            )
            ->orderBy('m.'. $orderByAttribute, $orderByDirection);

        if (!empty($brand)) {
            $qb->where(
                $qb->expr()
                    ->eq('m.brand', ':brand')
            );
        }

        $q = $qb->getQuery();

        $q->setParameter('userId', $userId);

        if (!empty($brand)) {
            $q->setParameter('brand', $brand);
        }

        return $q->getResult();
    }

    /**
     * @param integer $subscriptionId
     * @param string|null $brand // gets for all brands if null
     * @param string $orderByAttribute
     * @param string $orderByDirection
     *
     * @return MembershipAction[]
     */
    public function getAllSubscriptionsMembershipActions(
        $subscriptionId,
        $brand = null,
        $orderByAttribute = 'createdAt',
        $orderByDirection = 'desc'
    )
    {
        $qb = $this->createQueryBuilder('m');

        $qb->select(['m'])
            ->where(
                $qb->expr()
                    ->eq('m.subscription', ':subscriptionId')
            )
            ->orderBy($orderByAttribute, $orderByDirection);

        if (!empty($brand)) {
            $qb->where(
                $qb->expr()
                    ->eq('m.brand', ':brand')
            );
        }

        $q = $qb->getQuery();

        $q->setParameter('subscriptionId', $subscriptionId);

        if (!empty($brand)) {
            $q->setParameter('brand', $brand);
        }

        return $q->getResult();
    }

    /**
     * @param MembershipAction $membershipAction
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function persist(MembershipAction $membershipAction)
    {
        $this->getEntityManager()->persist($membershipAction);
        $this->getEntityManager()->flush($membershipAction);
    }
}
