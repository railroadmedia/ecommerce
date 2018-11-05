<?php

namespace Railroad\Ecommerce\Decorators;

use Railroad\Ecommerce\Entities\Product;
use Railroad\Resora\Decorators\Entity\EntityDecorator;

class ProductEntityDecorator extends EntityDecorator
{
    public function decorate($results)
    {
        foreach ($results as $resultsIndex => $result) {
            if (!($result instanceof Product)) {
                $results[$resultsIndex] = new Product((array)$result);
            }
        }

        return $results;
    }
}