<?php

namespace Railroad\Ecommerce\Decorators;

use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Resora\Decorators\Entity\EntityDecorator;

class PaymentMethodEntityDecorator extends EntityDecorator
{
    public function decorate($results)
    {
        foreach ($results as $resultsIndex => $result) {
            if (!($result instanceof PaymentMethod)) {
                $results[$resultsIndex] = new PaymentMethod((array)$result);
            }
        }

        return $results;
    }
}