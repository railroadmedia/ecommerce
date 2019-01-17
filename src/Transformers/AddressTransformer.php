<?php

namespace Railroad\Ecommerce\Transformers;

use Carbon\Carbon;
use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\Address;

class AddressTransformer extends TransformerAbstract
{
    // todo: user and customer mappings

    public function transform(Address $address)
    {
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
            'created_at' => Carbon::instance($address->getCreatedAt())
                ->toDateTimeString(),
            'updated_at' => Carbon::instance($address->getUpdatedAt())
                ->toDateTimeString(),
        ];
    }
}