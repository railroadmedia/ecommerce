<?php

namespace Railroad\Ecommerce\Repositories\QueryBuilders;


use Illuminate\Database\Query\Builder;
use Railroad\Ecommerce\Services\ConfigService;

class QueryBuilder extends Builder
{
    /**
     * @param integer $page
     * @param integer $limit
     * @return $this
     */
    public function directPaginate($page, $limit)
    {
        if ($limit >= 1) {
            $this->limit($limit)
                ->skip(($page - 1) * $limit);
        }

        return $this;
    }

    /**
     * @param null $column
     * @param string $direction
     * @return $this
     */
    public function orderBy($column = null, $direction = 'asc', $table = null)
    {
        if ($column) {
            parent::orderBy($table . '.' . $column, $direction);
        }

        return $this;
    }

    /**
     * @param array $columns
     * @return array
     */
    public function getToArray(array $columns = ['*'])
    {
        return parent::get($columns)->toArray();
    }
}