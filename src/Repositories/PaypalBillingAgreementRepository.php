<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Railroad\Ecommerce\Entities\PaypalBillingAgreement;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

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
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct(
            $em,
            $em->getClassMetadata(PaypalBillingAgreement::class)
        );
    }

    /**
     * Returns an array of PaypalBillingAgreement, with keys as PaypalBillingAgreements ids
     *
     * @param array $paypalIds
     *
     * @return array
     */
    public function getPaypalAgreementsMap(?array $paypalIds = []): array
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $paypalAgreements =
            $qb->select('p')
                ->from($this->getClassName(), 'p')
                ->where(
                    $qb->expr()
                        ->in('p.id', ':paypalIds')
                )
                ->setParameter('paypalIds', $paypalIds)
                ->getQuery()
                ->getResult();

        $results = [];

        foreach ($paypalAgreements as $paypalAgreement) {
            /** @var $paypalAgreement PaypalBillingAgreement */
            $results[$paypalAgreement->getId()] = $paypalAgreement;
        }

        return $results;
    }
}
