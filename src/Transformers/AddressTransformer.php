<?php

namespace Railroad\Ecommerce\Transformers;

use Doctrine\Common\Persistence\Proxy;
use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
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
            'first_name' => utf8_encode($address->getFirstName()),
            'last_name' => utf8_encode($address->getLastName()),
            'street_line_1' => utf8_encode($address->getStreetLine1()),
            'street_line_2' => utf8_encode($address->getStreetLine2()),
            'city' => utf8_encode($address->getCity()),
            'zip' => utf8_encode($address->getZip()),
            'region' => utf8_encode($address->getRegion()),
            'country' => utf8_encode($address->getCountry()),
            'note' => $address->getNote(),
            'created_at' => $address->getCreatedAt() ?
                $address->getCreatedAt()
                    ->toDateTimeString() : null,
            'updated_at' => $address->getUpdatedAt() ?
                $address->getUpdatedAt()
                    ->toDateTimeString() : null,
            'deleted_at' => $address->getDeletedAt() ?
                $address->getDeletedAt()
                    ->toDateTimeString() : null,
        ];
    }

    public function includeUser(Address $address)
    {
        $userProvider = app()->make(UserProviderInterface::class);

        $userTransformer = $userProvider->getUserTransformer();

        return $this->item(
            $address->getUser(),
            $userTransformer,
            'user'
        );
    }

    public function includeCustomer(Address $address)
    {
        if ($address->getCustomer() instanceof Proxy) {
            return $this->item(
                $address->getCustomer(),
                new EntityReferenceTransformer(),
                'customer'
            );
        }
        else {
            return $this->item(
                $address->getCustomer(),
                new CustomerTransformer(),
                'customer'
            );
        }
    }
}
