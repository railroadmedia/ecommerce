<?php

namespace Railroad\Ecommerce\Decorators;

use Railroad\Ecommerce\Entities\Address;
use Railroad\Resora\Decorators\Entity\EntityDecorator;

class AddressEntityDecorator extends EntityDecorator
{
    public function decorate($results)
    {
        foreach ($results as $resultsIndex => $result) {
            if (!($result instanceof Address)) {
                $results[$resultsIndex] = new Address((array)$result);
            }
        }

        return $results;
    }
}