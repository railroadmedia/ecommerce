<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\ORMException;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Composites\Query\ResultsQueryBuilderComposite;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\Traits\UseFormRequestQueryBuilder;

/**
 * Class AddressRepository
 * @package Railroad\Ecommerce\Repositories
 */
class AddressRepository extends RepositoryBase
{
    use UseFormRequestQueryBuilder {
        indexByRequest as baseIndexByRequest;
    }

    /**
     * CreditCardRepository constructor.
     *
     * @param EcommerceEntityManager $entityManager
     */
    public function __construct(EcommerceEntityManager $entityManager)
    {
        parent::__construct($entityManager, $entityManager->getClassMetadata(Address::class));
    }

    /**
     * @param $id
     *
     * @return Address|null
     *
     * @throws ORMException
     */
    public function byId($id): ?Address
    {
        $qb = $this->entityManager->createQueryBuilder();

        $q =
            $qb->select('a')
                ->from(Address::class, 'a')
                ->where('a.id = :id')
                ->getQuery()
                ->setParameter('id', $id)
                ->setResultCacheDriver($this->arrayCache);

        return $q->getResult()[0] ?? null;
    }

    /**
     * @param Request $request
     * @param User $user
     * @return ResultsQueryBuilderComposite
     */
    public function indexByRequest(Request $request, User $user)
    {
        $alias = 'a';

        $qb = $this->createQueryBuilder($alias);

        $qb->paginateByRequest($request)
            ->orderByRequest($request, $alias)
            ->restrictBrandsByRequest($request, $alias)
            ->select($alias)
            ->andWhere(
                $qb->expr()
                    ->eq('a.user', ':user')
            )
            ->setParameter('user', $user);

        $results =
            $qb->getQuery()
                ->getResult();

        return new ResultsQueryBuilderComposite($results, $qb);
    }
}
