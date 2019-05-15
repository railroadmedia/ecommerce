<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\Customer;

class CustomerTransformer extends TransformerAbstract
{
    public function transform(Customer $customer)
    {
        return [
            'id' => $customer->getId(),
            'brand' => $customer->getBrand(),
            'phone' => $customer->getPhone(),
            'email' => $customer->getEmail(),
            'note' => $customer->getNote(),
            'created_at' => $customer->getCreatedAt() ?
                $customer->getCreatedAt()
                    ->toDateTimeString() : null,
            'updated_at' => $customer->getUpdatedAt() ?
                $customer->getUpdatedAt()
                    ->toDateTimeString() : null,
        ];
    }
}
