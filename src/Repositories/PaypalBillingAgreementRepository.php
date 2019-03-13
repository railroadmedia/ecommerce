<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\PaypalBillingAgreement;

/**
 * Class PaypalBillingAgreementRepository
 *
 * @method PaypalBillingAgreement find($id, $lockMode = null, $lockVersion = null)
 * @method PaypalBillingAgreement findOneBy(array $criteria, array $orderBy = null)
 * @method PaypalBillingAgreement[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method PaypalBillingAgreement[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class PaypalBillingAgreementRepository extends EntityRepository
{
    /**
     * PaypalBillingAgreementRepository constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(PaypalBillingAgreement::class));
    }

    /**
     * Returns an array of PaypalBillingAgreement, with keys as PaypalBillingAgreements ids
     *
     * @param array $creditCardIds
     *
     * @return array
     */
    public function getPaypalAgreementsMap(?array $paypalIds = []): array
    {
        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder();

        $paypalAgreements = $qb
            ->select('p')
            ->from($this->getClassName(), 'p')
            ->where($qb->expr()->in('p.id', ':paypalIds'))
            ->setParameter('paypalIds', $paypalIds)
            ->getQuery()
            ->getResult();

        $results = [];

        foreach ($paypalAgreements as $paypalAgreement) {
            /**
             * @var $paypalAgreement \Railroad\Ecommerce\Entities\PaypalBillingAgreement
             */
            $results[$paypalAgreement->getId()] = $paypalAgreement;
        }

        return $results;
    }
}
