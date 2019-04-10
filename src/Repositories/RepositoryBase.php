<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\RedisCache;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class RepositoryBase
 * @package Railroad\Ecommerce\Repositories
 */
class RepositoryBase
{
    /**
     * @var EcommerceEntityManager
     */
    protected $entityManager;

    /**
     * @var ArrayCache
     */
    protected $arrayCache;

    /**
     * @var RedisCache
     */
    protected $redisCache;

    /**
     * ProductRepository constructor.
     *
     * @param EcommerceEntityManager $entityManager
     */
    public function __construct(EcommerceEntityManager $entityManager)
    {
        $this->entityManager = $entityManager;

        $this->arrayCache = app()->make('EcommerceArrayCache');
        $this->redisCache = app()->make('EcommerceRedisCache');
    }
}
