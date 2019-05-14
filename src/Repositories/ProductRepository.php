<?php

namespace Railroad\Ecommerce\Repositories;

use Railroad\Ecommerce\Entities\AccessCode;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class ProductRepository
 * @package Railroad\Ecommerce\Repositories
 */
class ProductRepository extends RepositoryBase
{
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
     * @return Product[]
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
     */
    public function byAccessCode(AccessCode $accessCode): array
    {
        return $this->byAccessCodes([$accessCode]);
    }

    /**
     * @param Cart $cart
     *
     * @return Product[]
     */
    public function byCart(Cart $cart)
    {
        return $this->bySkus($cart->listSkus());
    }
}
