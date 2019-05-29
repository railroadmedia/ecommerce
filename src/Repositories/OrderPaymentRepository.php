<?php

namespace Railroad\Ecommerce\Repositories;

use Carbon\Carbon;
use Doctrine\ORM\QueryBuilder;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\OrderPayment;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class OrderPaymentRepository
 *
 * @method OrderPayment find($id, $lockMode = null, $lockVersion = null)
 * @method OrderPayment findOneBy(array $criteria, array $orderBy = null)
 * @method OrderPayment[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method OrderPayment[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class OrderPaymentRepository extends RepositoryBase
{
    /**
     * OrderPaymentRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(OrderPayment::class));
    }

    /**
     * @param Payment $payment
     *
     * @return OrderPayment[]
     */
    public function getByPayment(Payment $payment): array
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select(['op', 'p'])
            ->from(OrderPayment::class, 'op')
            ->join('op.payment', 'p')
            ->where(
                $qb->expr()
                    ->eq('op.payment', ':payment')
            )
            ->andWhere(
                $qb->expr()
                    ->isNull('p.deletedOn')
            )
            ->setParameter('payment', $payment);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Request $request
     *
     * @return Order[]
     */
    public function getOrderPaymentsForStats(Request $request): array
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

        $qb->select(['op', 'o', 'oi', 'p', 'py'])
            ->from(OrderPayment::class, 'op')
            ->join('op.order', 'o')
            ->join('op.payment', 'py')
            ->join('o.orderItems', 'oi')
            ->join('oi.product', 'p')
            ->where(
                $qb->expr()
                    ->between('py.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->setParameter('smallDateTime', $smallDateTime)
            ->setParameter('bigDateTime', $bigDateTime);

        if ($request->has('brand')) {
            $qb->andWhere('o.brand = :brand')
                ->setParameter('brand', $request->get('brand'));
        }

        return
            $qb->getQuery()
                ->getResult();
    }
}
