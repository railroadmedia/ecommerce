<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\AccessCode;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class ProductRepository
 *
 * @method Product find($id, $lockMode = null, $lockVersion = null)
 * @method Product findOneBy(array $criteria, array $orderBy = null)
 * @method Product[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Product[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class ProductRepository extends EntityRepository
{
    /**
     * @var ArrayCache
     */
    protected $arrayCache;

    /**
     * ProductRepository constructor.
     *
     * @param  EcommerceEntityManager  $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(Product::class));

        $this->arrayCache = new ArrayCache();
    }

    /**
     * Returns an array of Products from $accessCode productsIds
     *
     * @param  AccessCode  $accessCode
     *
     * @return array
     */
    public function getAccessCodeProducts(AccessCode $accessCode): array
    {
        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('p')->from($this->getClassName(), 'p')->where($qb->expr()
                ->in('p.id', ':ids'));

        /**
         * @var $q \Doctrine\ORM\Query
         */
        $q = $qb->getQuery();

        $q->setParameter('ids', $accessCode->getProductIds());

        return $q->getResult();
    }

    /**
     * Returns an array of Products from $accessCodes productsIds
     *
     * @param  array  $accessCodes
     *
     * @return array
     */
    public function getAccessCodesProducts(array $accessCodes): array
    {
        $productIds = [];

        foreach ($accessCodes as $accessCode) {
            $accessCodeProductIds = array_flip($accessCode->getProductIds());

            $productIds += $accessCodeProductIds;
        }

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('p')->from($this->getClassName(), 'p')->where($qb->expr()
            ->in('p.id', ':ids'));

        /**
         * @var $q \Doctrine\ORM\Query
         */
        $q = $qb->getQuery();

        $q->setParameter('ids', array_keys($productIds));

        return $q->getResult();
    }

    /**
     * We use the array cache here since we want to grab entities from the ORM
     * instead of a new query and products rarely change.
     *
     * @param  array  $skus
     *
     * @return Product[]
     */
    public function findBySkus(array $skus)
    {
        $products = [];
        $skusNotInCache = [];

        foreach ($skus as $sku) {
            $product = $this->arrayCache->fetch($sku);

            if ($product === false) {
                $skusNotInCache[] = $sku;
            } else {
                $products[] = $product;
            }
        }

        $products = array_merge($products,
            $this->findBy(['sku' => $skusNotInCache]));

        foreach ($products as $product) {
            $this->arrayCache->save($product->getSku(), $product);
        }

        return $products;
    }

    /**
     * @param  string  $sku
     *
     * @return null|Product
     */
    public function findOneBySku(string $sku)
    {
        $product = $this->arrayCache->fetch($sku);

        if ($product === false) {
            $productFromDatabase = $this->findOneBy(['sku' => $sku]);

            $this->arrayCache->save($productFromDatabase->getSku(),
                $productFromDatabase);

            $product = $productFromDatabase;
        }

        return $product;
    }
}
