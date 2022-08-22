<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
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
     * @var ClassMetadata
     */
    protected $entityClassMetaData;

    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var ArrayCache
     */
    protected $arrayCache;

    /**
     * @param EcommerceEntityManager $entityManager
     * @param ClassMetadata $entityClassMetaData
     */
    public function __construct(EcommerceEntityManager $entityManager, ClassMetadata $entityClassMetaData)
    {
        $this->entityManager = $entityManager;
        $this->entityClassMetaData = $entityClassMetaData;
        $this->entityName = $entityClassMetaData->name;

        $this->arrayCache = app()->make('EcommerceArrayCache');
    }

    /**
     * @return EcommerceEntityManager
     */
    protected function getEntityManager(): EcommerceEntityManager
    {
        return $this->entityManager;
    }

    /**
     * @return string
     */
    protected function getClassName(): string
    {
        return $this->entityName;
    }

    /**
     * @param string $alias
     * @param null $indexBy
     * @return QueryBuilder
     */
    public function createQueryBuilder($alias, $indexBy = null)
    {
        $queryBuilder = new QueryBuilder($this->entityManager);

        $queryBuilder->select($alias)
            ->from($this->entityName, $alias, $indexBy);

        return $queryBuilder;
    }

    /**
     * Finds an entity by its primary key / identifier.
     *
     * @param int $id The identifier.
     *
     * @return bool|Entity|object
     *
     * @throws ORMException
     */
    public function find(int $id)
    {
        $entity =
            $this->entityManager->getUnitOfWork()
                ->tryGetById($id, $this->entityName);

        if ($entity !== false) {
            return $entity;
        }

        $qb = $this->entityManager->createQueryBuilder();

        $q =
            $qb->select('a')
                ->from($this->entityName, 'a')
                ->where('a.id = :id')
                ->getQuery()
                ->setParameter('id', $id);

        return $q->getResult()[0] ?? null;
    }

    /**
     * Finds all entities in the repository.
     *
     * @return array The entities.
     */
    public function findAll()
    {
        return $this->findBy([]);
    }

    /**
     * Finds entities by a set of criteria.
     *
     * @param array $criteria
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     *
     * @return array The objects.
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $persister =
            $this->entityManager->getUnitOfWork()
                ->getEntityPersister($this->entityName);

        return $persister->loadAll($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Finds a single entity by a set of criteria.
     *
     * @param array $criteria
     * @param array|null $orderBy
     *
     * @return object|null The entity instance or NULL if the entity can not be found.
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        $persister =
            $this->entityManager->getUnitOfWork()
                ->getEntityPersister($this->entityName);

        return $persister->load($criteria, null, null, [], null, 1, $orderBy);
    }

    /**
     * Counts entities by a set of criteria.
     *
     * @param array $criteria
     *
     * @return int The cardinality of the objects that match the given criteria.
     *
     */
    public function count(array $criteria)
    {
        return $this->entityManager->getUnitOfWork()
            ->getEntityPersister($this->entityName)
            ->count($criteria);
    }
}
