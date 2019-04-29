<?php

namespace Railroad\Ecommerce\Composites\Query;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\QueryBuilder;

class ResultsQueryBuilderComposite
{
    /**
     * @var array|Entity
     */
    protected $results;

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * ResultsAndQueryBuilderComposite constructor.
     * @param array|Entity $results
     * @param QueryBuilder $queryBuilder
     */
    public function __construct($results, QueryBuilder $queryBuilder)
    {
        $this->results = $results;
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @return array|Entity
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }
}