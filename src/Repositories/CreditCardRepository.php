<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\CreditCard;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class CreditCardRepository
 *
 * @method CreditCard find($id, $lockMode = null, $lockVersion = null)
 * @method CreditCard findOneBy(array $criteria, array $orderBy = null)
 * @method CreditCard[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method CreditCard[] findByExternalId(string $externalId)
 * @method CreditCard[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class CreditCardRepository extends EntityRepository
{
    /**
     * CreditCardRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(CreditCard::class));
    }

    /**
     * Returns an array of credit cards, with keys as credit card ids
     *
     * @param array $creditCardIds
     *
     * @return array
     */
    public function getCreditCardsMap(?array $creditCardIds = []): array
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
            /**
             * @var $creditCard \Railroad\Ecommerce\Entities\CreditCard
             */
            $results[$creditCard->getId()] = $creditCard;
        }

        return $results;
    }
}
