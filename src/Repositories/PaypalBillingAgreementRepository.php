<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\PaypalBillingAgreement;

class PaypalBillingAgreementRepository extends EntityRepository
{
    public function getPaypalAgreementsMap($paypalIds = [])
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
            $results[$paypalAgreement->getId()] = $paypalAgreement;
        }

        return $results;
    }
}
