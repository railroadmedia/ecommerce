<?php

namespace Railroad\Ecommerce\Doctrine;

use Doctrine\Common\Persistence\AbstractManagerRegistry;
use ProxyManager\Proxy\LazyLoadingInterface;
use Illuminate\Contracts\Container\Container;

class ManagerRegistry extends AbstractManagerRegistry
{
    const MANAGER_BINDING_PREFIX = 'doctrine.managers.';

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var EntityManagerFactory
     */
    protected $entityManagerFactory;

    public function __construct(
        Container $container,
        EntityManagerFactory $entityManagerFactory
    ) {
        $this->container = $container;
        $this->entityManagerFactory = $entityManagerFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function getService($name)
    {
        return $this->container->make($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function resetService($name)
    {
        $this->container->forgetInstance($name);
    }

    // add method for adding managers to registry
}
