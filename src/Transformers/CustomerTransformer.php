<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\Customer;

class CustomerTransformer extends TransformerAbstract
{
    protected $defaultIncludes = [];
    protected $customersOrdersMap;

    /**
     * CustomerTransformer constructor.
     *
     * @param array $customersOrdersMap
     */
    public function __construct(array $customersOrdersMap = [])
    {
        $this->customersOrdersMap = $customersOrdersMap;

        if (!empty($customersOrdersMap)) {
            $this->defaultIncludes[] = 'order';
        }
    }

    /**
     * @param Customer $customer
     *
     * @return array
     */
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

    /**
     * @param Customer $customer
     *
     * @return Item|null
     */
    public function includeOrder(Customer $customer)
    {
        if (!isset($this->customersOrdersMap[$customer->getId()])) {
            return null;
        }

        $transformer = new OrderTransformer();
        $defaultIncludes = $transformer->getDefaultIncludes();
        $transformer->setDefaultIncludes(array_diff($defaultIncludes, ['customer']));

        return $this->item(
            $this->customersOrdersMap[$customer->getId()],
            $transformer,
            'order'
        );
    }
}
