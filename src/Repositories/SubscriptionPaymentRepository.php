<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\QueryBuilder;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\SubscriptionPayment;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class SubscriptionPaymentRepository
 *
 * @method SubscriptionPayment find($id, $lockMode = null, $lockVersion = null)
 * @method SubscriptionPayment findOneBy(array $criteria, array $orderBy = null)
 * @method SubscriptionPayment[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method SubscriptionPayment[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class SubscriptionPaymentRepository extends RepositoryBase
{
    /**
     * SubscriptionPaymentRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct(
            $em,
            $em->getClassMetadata(SubscriptionPayment::class)
        );
    }

    /**
     * @param Payment $payment
     *
     * @return SubscriptionPayment[]
     */
    public function getByPayment(Payment $payment): array
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select(['sp', 's'])
            ->from(SubscriptionPayment::class, 'sp')
            ->join('sp.subscription', 's')
            ->where(
                $qb->expr()
                    ->eq('sp.payment', ':payment')
            )
            ->setParameter('payment', $payment);

        return $qb->getQuery()->getResult();
    }

    public function getByPayments($payments): array
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select(['sp', 's'])
            ->from(SubscriptionPayment::class, 'sp')
            ->join('sp.subscription', 's')
            ->where(
                $qb->expr()
                    ->in('sp.payment', ':payments')
            )
            ->setParameter('payments', $payments);

        $subscriptionPayments = $qb->getQuery()->getResult();

        $result = [];
        foreach ($subscriptionPayments as $subscriptionPayment) {
            /** @var SubscriptionPayment $subscriptionPayment */
            $result[$subscriptionPayment->getPayment()->getId()] = $subscriptionPayment;
        }
        return $result;
    }
}
