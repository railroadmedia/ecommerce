<?php

namespace Railroad\Ecommerce\Repositories;

use App\Modules\Ecommerce\Models\UserPaymentMethod;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\ORMException;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\Customer;
use Railroad\Ecommerce\Entities\CustomerPaymentMethods;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\UserPaymentMethods;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\Traits\UseFormRequestQueryBuilder;

/**
 * Class PaymentMethodRepository
 * @package Railroad\Ecommerce\Repositories
 */
class PaymentMethodRepository extends RepositoryBase
{
    use UseFormRequestQueryBuilder;

    /**
     * CreditCardRepository constructor.
     *
     * @param EcommerceEntityManager $entityManager
     */
    public function __construct(EcommerceEntityManager $entityManager)
    {
        parent::__construct($entityManager, $entityManager->getClassMetadata(PaymentMethod::class));
    }

    /**
     * @param int $id
     *
     * @return PaymentMethod|null
     *
     * @throws ORMException
     */
    public function byId(int $id): ?PaymentMethod
    {
        $qb = $this->entityManager->createQueryBuilder();

        $q =
            $qb->select(['pm', 'cc', 'ppba'])
                ->from(PaymentMethod::class, 'pm')
                ->leftJoin('pm.creditCard', 'cc')
                ->leftJoin('pm.paypalBillingAgreement', 'ppba')
                ->where('pm.id = :id')
                ->getQuery()
                ->setParameter('id', $id);

        return $q->getOneOrNullResult();
    }

    /**
     * @param $userId
     * @param $paymentMethodId
     *
     * @return PaymentMethod|null
     *
     * @throws ORMException
     */
    public function getUsersPaymentMethodById($userId, $paymentMethodId)
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select(['pm', 'cc', 'ppba'])
            ->from(PaymentMethod::class, 'pm')
            ->join(
                UserPaymentMethods::class,
                'upm',
                Join::WITH,
                $qb->expr()
                    ->eq(1, 1)
            )
            ->join('upm.paymentMethod', 'pmj')
            ->leftJoin('pm.creditCard', 'cc')
            ->leftJoin('pm.paypalBillingAgreement', 'ppba')
            ->where('upm.user = :userId')
            ->andWhere('pmj.id = pm.id')
            ->andWhere('pm.id = :paymentMethodId')
            ->setParameter('userId', $userId)
            ->setParameter('paymentMethodId', $paymentMethodId);

        return $qb->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param $customerId
     * @param $paymentMethodId
     *
     * @return PaymentMethod|null
     *
     * @throws ORMException
     */
    public function getCustomersPaymentMethodById($customerId, $paymentMethodId)
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select(['pm', 'cc', 'ppba'])
            ->from(PaymentMethod::class, 'pm')
            ->join(
                CustomerPaymentMethods::class,
                'cpm',
                Join::WITH,
                $qb->expr()
                    ->eq(1, 1)
            )
            ->join('cpm.paymentMethod', 'pmj')
            ->leftJoin('pm.creditCard', 'cc')
            ->leftJoin('pm.paypalBillingAgreement', 'ppba')
            ->where('cpm.customer = :customerId')
            ->andWhere('pmj.id = pm.id')
            ->andWhere('pm.id = :paymentMethodId')
            ->setParameter('customerId', $customerId)
            ->setParameter('paymentMethodId', $paymentMethodId);

        return $qb->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param $userId
     *
     * @return PaymentMethod|null
     *
     * @throws ORMException
     */
    public function getUsersPrimaryPaymentMethod($userId)
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select(['pm', 'cc', 'ppba'])
            ->from(PaymentMethod::class, 'pm')
            ->join(
                UserPaymentMethods::class,
                'upm',
                Join::WITH,
                $qb->expr()
                    ->eq(1, 1)
            )
            ->join('upm.paymentMethod', 'pmj')
            ->leftJoin('pm.creditCard', 'cc')
            ->leftJoin('pm.paypalBillingAgreement', 'ppba')
            ->where('upm.user = :userId')
            ->andWhere('pmj.id = pm.id')
            ->andWhere('upm.isPrimary = true')
            ->orderBy('pm.id', 'desc')
            ->setParameter('userId', $userId);
        return $qb->getQuery()
            ->getResult()[0] ?? null;
    }

    /**
     * @param $userId
     * @param Request $request
     * @param string $brand
     *
     * @return PaymentMethod[]
     */
    public function getAllUsersPaymentMethods(
        $userId,
        Request $request,
        $brand = null
    ) {
        $alias = 'pm';
        $qb = $this->createQueryBuilder($alias);

        $qb->select(['upm', 'pm', 'cc', 'ppba'])
            ->restrictSoftDeleted($request, $alias)
            ->join('pm.userPaymentMethod', 'upm')
            ->leftJoin('pm.creditCard', 'cc')
            ->leftJoin('pm.paypalBillingAgreement', 'ppba')
            ->where(
                $qb->expr()
                    ->eq('upm.user', ':user')
            )
            ->orderBy('pm.createdAt', 'desc')
            ->setParameter('user', $userId);

        if ($brand) {
            $qb->andWhere(
                $qb->expr()
                    ->orX(
                        $qb->expr()
                            ->andX(
                                $qb->expr()
                                    ->isNotNull('cc.id'),
                                $qb->expr()
                                    ->eq('cc.paymentGatewayName', ':ccBrand')
                            ),
                        $qb->expr()
                            ->andX(
                                $qb->expr()
                                    ->isNotNull('cc.id'),
                                $qb->expr()
                                    ->eq('cc.paymentGatewayName', ':mGateway')
                            ),
                        $qb->expr()
                            ->andX(
                                $qb->expr()
                                    ->isNotNull('ppba.id'),
                                $qb->expr()
                                    ->eq('ppba.paymentGatewayName', ':ppbaBrand')
                            )
                        ,
                        $qb->expr()
                            ->andX(
                                $qb->expr()
                                    ->isNotNull('ppba.id'),
                                $qb->expr()
                                    ->eq('ppba.paymentGatewayName', ':mGateway')
                            )
                    )
            )
                ->setParameter('mGateway', "musora")
                ->setParameter('ccBrand', $brand)
                ->setParameter('ppbaBrand', $brand);
        }

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @param Customer $customer
     * @param string $brand
     *
     * @return PaymentMethod[]
     */
    public function getCustomerPaymentMethods(
        Customer $customer,
        $brand = null
    ) {
        $alias = 'pm';

        $qb = $this->createQueryBuilder($alias);

        $qb->select(['cpm', 'pm', 'cc', 'ppba'])
            ->join('pm.customerPaymentMethod', 'cpm')
            ->leftJoin('pm.creditCard', 'cc')
            ->leftJoin('pm.paypalBillingAgreement', 'ppba')
            ->where(
                $qb->expr()
                    ->eq('cpm.customer', ':customer')
            )
            ->orderBy('pm.createdAt', 'desc')
            ->setParameter('customer', $customer);

        if ($brand) {
            $qb->andWhere(
                $qb->expr()
                    ->orX(
                        $qb->expr()
                            ->andX(
                                $qb->expr()
                                    ->isNotNull('cc.id'),
                                $qb->expr()
                                    ->eq('cc.paymentGatewayName', ':ccBrand')
                            ),
                        $qb->expr()
                            ->andX(
                                $qb->expr()
                                    ->isNotNull('ppba.id'),
                                $qb->expr()
                                    ->eq('ppba.paymentGatewayName', ':ppbaBrand')
                            )
                    )
            )
                ->setParameter('ccBrand', $brand)
                ->setParameter('ppbaBrand', $brand);
        }

        return $qb->getQuery()
            ->getResult();
    }
}
