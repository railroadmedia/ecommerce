<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\CreditCard;

class CreditCardRepository extends EntityRepository
{
    public function getCreditCardsMap($creditCardIds = [])
    {
        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder();

        $creditCards = $qb
            ->select('c')
            ->from($this->getClassName(), 'c')
            ->where($qb->expr()->in('c.id', ':creditCardIds'))
            ->setParameter('creditCardIds', $creditCardIds)
            ->getQuery()
            ->getResult();

        $results = [];

        foreach ($creditCards as $creditCard) {
            $results[$creditCard->getId()] = $creditCard;
        }

        return $results;
    }
}
