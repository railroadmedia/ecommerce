<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\ORMException;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Composites\Query\ResultsQueryBuilderComposite;
use Railroad\Ecommerce\Entities\AccessCode;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\QueryBuilders\FromRequestEcommerceQueryBuilder;
use Railroad\Ecommerce\Repositories\Traits\UseFormRequestQueryBuilder;

/**
 * Class ProductRepository
 * @package Railroad\Ecommerce\Repositories
 */
class ProductRepository extends RepositoryBase
{
    use UseFormRequestQueryBuilder;

    /**
     * @var array
     */
    private $cache = [];

    /**
     * CreditCardRepository constructor.
     *
     * @param EcommerceEntityManager $entityManager
     */
    public function __construct(EcommerceEntityManager $entityManager)
    {
        parent::__construct($entityManager, $entityManager->getClassMetadata(Product::class));
    }

    /**
     * @param Request $request
     * @param array $activity - default [1] - active products
     *
     * @return ResultsQueryBuilderComposite
     */
    public function indexByRequest(Request $request, ?array $activity = [1]): ResultsQueryBuilderComposite
    {
        $alias = 'p';

        /** @var $qb FromRequestEcommerceQueryBuilder */
        $qb = $this->createQueryBuilder($alias);

        $qb->select($alias)
            ->paginateByRequest($request)
            ->orderByRequest($request, $alias)
            ->andWhere(
                $qb->expr()
                    ->in($alias . '.active', ':activity')
            )
            ->setParameter('activity', $activity);

        $results =
            $qb->getQuery()
                ->getResult();

        return new ResultsQueryBuilderComposite($results, $qb);
    }

    /**
     * @param int $productId
     * @param array $activity - default [1] - active products
     *
     * @return Product|null
     *
     * @throws NonUniqueResultException
     */
    public function findProduct(int $productId, ?array $activity = [1]): ?Product
    {
        $alias = 'p';

        /** @var $qb FromRequestEcommerceQueryBuilder */
        $qb = $this->createQueryBuilder($alias);

        $qb->where(
            $qb->expr()
                ->in('p.active', ':activity')
        )
            ->andWhere(
                $qb->expr()
                    ->eq('p.id', ':id')
            )
            ->setParameter('activity', $activity)
            ->setParameter('id', $productId);

        return $qb->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Product[]
     *
     * @throws ORMException
     */
    public function all()
    {
        $key = md5(get_class() . __FUNCTION__ . json_encode(func_get_args()));

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $qb = $this->entityManager->createQueryBuilder();

        $q =
            $qb->select('p')
                ->from(Product::class, 'p')
                ->getQuery();

        $this->cache[$key] = $q->getResult();

        return $this->cache[$key];
    }

    /**
     * @param array $skus
     *
     * @return Product[]
     *
     * @throws ORMException
     */
    public function bySkus(array $skus)
    {
        $key = md5(get_class() . __FUNCTION__ . json_encode(func_get_args()));

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $qb = $this->entityManager->createQueryBuilder();

        $q =
            $qb->select('p')
                ->from(Product::class, 'p')
                ->where(
                    $qb->expr()
                        ->in('p.sku', ':skus')
                )
                ->getQuery()
                ->setParameter('skus', $skus);

        $this->cache[$key] = $q->getResult();

        return $this->cache[$key];
    }

    /**
     * @param string $sku
     *
     * @return null|Product
     *
     * @throws ORMException
     */
    public function bySku(string $sku)
    {
        return $this->bySkus([$sku])[0] ?? null;
    }

    /**
     * Returns an array of Products from $accessCodes productsIds
     *
     * @param array $accessCodes
     *
     * @return Product[]
     *
     * @throws ORMException
     */
    public function byAccessCodes(array $accessCodes): array
    {
        $productIds = [];

        foreach ($accessCodes as $accessCode) {
            $accessCodeProductIds = array_flip($accessCode->getProductIds());

            $productIds += $accessCodeProductIds;
        }

        $qb = $this->entityManager->createQueryBuilder();

        $q =
            $qb->select('p')
                ->from(Product::class, 'p')
                ->where(
                    $qb->expr()
                        ->in('p.id', ':ids')
                )
                ->getQuery()
                ->setParameter('ids', array_keys($productIds));

        return $q->getResult();
    }

    /**
     * Returns an array of Products from $accessCode productsIds
     *
     * @param AccessCode $accessCode
     *
     * @return Product[]
     *
     * @throws ORMException
     */
    public function byAccessCode(AccessCode $accessCode): array
    {
        return $this->byAccessCodes([$accessCode]);
    }

    /**
     * @param Cart $cart
     *
     * @return Product[]
     *
     * @throws ORMException
     */
    public function byCart(Cart $cart)
    {
        return $this->bySkus($cart->listSkus());
    }

    public function getPlusMembershipSKU(string $brand, string $digitalAccessTimeIntervalType): ?Product
    {
        $qb = $this->entityManager->createQueryBuilder();

        $q = $qb->select('p')
            ->from(Product::class, 'p')
            ->where($qb->expr()->eq('p.type', ':type'))
            ->andWhere($qb->expr()->eq('p.brand', ':brand'))
            ->andWhere($qb->expr()->eq('p.digitalAccessType', ':digitalAccessType'))
            ->andWhere($qb->expr()->eq('p.digitalAccessTimeIntervalType', ':digitalAccessTimeIntervalType'))
            //->andWhere($qb->expr()->notLike('p.sku', '%trial%'))
            ->orderBy('p.id', 'desc')
            ->getQuery()
            ->setParameter('type', Product::TYPE_DIGITAL_SUBSCRIPTION)
            ->setParameter('brand', $brand)
            ->setParameter('digitalAccessType', Product::DIGITAL_ACCESS_TYPE_ALL_CONTENT_ACCESS)
            ->setParameter('digitalAccessTimeIntervalType', $digitalAccessTimeIntervalType);

        return $q->getResult()[0] ?? null;
    }

    public function getBasicMembershipSKU(string $brand, string $digitalAccessTimeIntervalType): ?Product
    {
        $qb = $this->entityManager->createQueryBuilder();

        $q = $qb->select('p')
            ->from(Product::class, 'p')
            ->where($qb->expr()->eq('p.type', ':type'))
            ->andWhere($qb->expr()->eq('p.brand', ':brand'))
            ->andWhere($qb->expr()->eq('p.digitalAccessType', ':digitalAccessType'))
            ->andWhere($qb->expr()->eq('p.digitalAccessTimeIntervalType', ':digitalAccessTimeIntervalType'))
            //->andWhere($qb->expr()->notLike('p.sku', '%trial%'))
            ->orderBy('p.id', 'desc')
            ->getQuery()
            ->setParameter('type', Product::TYPE_DIGITAL_SUBSCRIPTION)
            ->setParameter('brand', $brand)
            ->setParameter('digitalAccessType', Product::DIGITAL_ACCESS_TYPE_BASIC_CONTENT_ACCESS)
            ->setParameter('digitalAccessTimeIntervalType', $digitalAccessTimeIntervalType);

        return $q->getResult()[0] ?? null;
    }
}
