<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\Address;

class AddressTransformer extends TransformerAbstract
{
    protected $defaultIncludes = [];

    public function transform(Address $address)
    {
        if ($address->getUser()) {
            $this->defaultIncludes[] = 'user';
        }

        if ($address->getCustomer()) {
            $this->defaultIncludes[] = 'customer';
        }

        return [
            'id' => $address->getId(),
            'type' => $address->getType(),
            'brand' => $address->getBrand(),
            'first_name' => $address->getFirstName(),
            'last_name' => $address->getLastName(),
            'street_line_1' => $address->getStreetLine1(),
            'street_line_2' => $address->getStreetLine2(),
            'city' => $address->getCity(),
            'zip' => $address->getZip(),
            'state' => $address->getState(),
            'country' => $address->getCountry(),
            'created_at' => $address->getCreatedAt() ? $address->getCreatedAt()->toDateTimeString() : null,
            'updated_at' => $address->getUpdatedAt() ? $address->getUpdatedAt()->toDateTimeString() : null,
        ];
    }

    public function includeUser(Address $address)
    {
        return $this->item($address->getUser(), new EntityReferenceTransformer(), 'user');
    }

    public function includeCustomer(Address $address)
    {
        return $this->item($address->getCustomer(), new EntityReferenceTransformer(), 'customer');
    }
}
