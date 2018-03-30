<?php

namespace Railroad\Ecommerce\Repositories;

use Railroad\Ecommerce\Repositories\QueryBuilders\ProductQueryBuilder;
use Railroad\Ecommerce\Services\ConfigService;

class ProductRepository extends RepositoryBase
{

    /**
     * @var integer
     */
    protected $page;

    /**
     * @var integer
     */
    protected $limit;

    /**
     * @var string
     */
    protected $orderBy;

    /**
     * @var string
     */
    protected $orderDirection;

    /**
     * @return Builder
     */
    public function query()
    {
        return (new ProductQueryBuilder(
            $this->connection(),
            $this->connection()->getQueryGrammar(),
            $this->connection()->getPostProcessor()
        ))
            ->from(ConfigService::$tableProduct);
    }

    /** Get the products that meet the conditions.
     * If the pagination parameter are defined, the products are paginated
     * @param array $conditions
     * @return mixed
     */
    public function getProductsByConditions(array $conditions)
    {
        $query = $this->query()
            ->restrictBrand()
            ->restrictActive()
            ->where($conditions);
        if ($this->page) {
            $query->directPaginate($this->page, $this->limit);
        }
        return $query
            ->get()
            ->toArray();
    }

    /** Count all the products
     * @return int
     */
    public function countProducts()
    {
        $query = $this->query()
            ->restrictBrand()
            ->restrictActive();

        return $query->count();
    }

    /** Set the pagination parameters
     * @param int $page
     * @param int $limit
     * @param string $orderByDirection
     * @param string $orderByColumn
     * @return $this
     */
    public function setData($page, $limit, $orderByDirection, $orderByColumn)
    {
        $this->page = $page;
        $this->limit = $limit;
        $this->orderBy = $orderByColumn;
        $this->orderDirection = $orderByDirection;

        return $this;
    }
}