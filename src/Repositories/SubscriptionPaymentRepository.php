<?php

namespace Railroad\Ecommerce\Repositories;

use Carbon\Carbon;
use Doctrine\ORM\QueryBuilder;
use Illuminate\Http\Request;
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

    /**
     * @param Request $request
     *
     * @return SubscriptionPayment[]
     */
    public function getSubscriptionPaymentsForStats(Request $request): array
    {
        $smallDateTime =
            $request->get(
                'small_date_time',
                Carbon::now()
                    ->subDay()
                    ->toDateTimeString()
            );

        $bigDateTime =
            $request->get(
                'big_date_time',
                Carbon::now()
                    ->subDay()
                    ->toDateTimeString()
            );

        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select(['sp', 's', 'p'])
            ->from(SubscriptionPayment::class, 'sp')
            ->join('sp.subscription', 's')
            ->join('sp.payment', 'p')
            ->where(
                $qb->expr()
                    ->between('p.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->setParameter('smallDateTime', $smallDateTime)
            ->setParameter('bigDateTime', $bigDateTime);

        if ($request->has('brand')) {
            $qb->andWhere('s.brand = :brand')
                ->setParameter('brand', $request->get('brand'));
        }

        return $qb->getQuery()->getResult();
    }
}
