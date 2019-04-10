<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\ORMException;
use Railroad\Ecommerce\Entities\AccessCode;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\Cart;

/**
 * Class ProductRepository
 * @package Railroad\Ecommerce\Repositories
 */
class ProductRepository extends RepositoryBase
{
    /**
     * @return Product[]
     * @throws ORMException
     */
    public function all()
    {
        $qb = $this->entityManager->createQueryBuilder();

        $q =
            $qb->select('p')
                ->from(Product::class, 'p')
                ->getQuery()
                ->setResultCacheDriver($this->arrayCache);

        return $q->getResult();
    }

    /**
     * @param array $skus
     *
     * @return Product[]
     * @throws ORMException
     */
    public function bySkus(array $skus)
    {
        $qb = $this->entityManager->createQueryBuilder();

        $q =
            $qb->select('p')
                ->from(Product::class, 'p')
                ->where(
                    $qb->expr()
                        ->in('p.sku', ':skus')
                )
                ->getQuery()
                ->setParameter('skus', $skus)
                ->setResultCacheDriver($this->arrayCache);

        return $q->getResult();
    }

    /**
     * @param string $sku
     *
     * @return null|Product
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
                ->setResultCacheDriver($this->arrayCache)
                ->setParameter('ids', array_keys($productIds));

        return $q->getResult();
    }

    /**
     * Returns an array of Products from $accessCode productsIds
     *
     * @param AccessCode $accessCode
     *
     * @return Product[]
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
     * @throws ORMException
     */
    public function byCart(Cart $cart)
    {
        return $this->bySkus($cart->listSkus());
    }
}
