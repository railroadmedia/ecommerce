<?php
namespace Railroad\Ecommerce\Entities;

use Railroad\Resora\Entities\Entity;

class PaymentMethod extends Entity
{
    public function dot()
    {
        $this->replace(array_merge($this->getArrayCopy(), (array)$this['method'] ?? []));

        return parent::dot();
    }
}