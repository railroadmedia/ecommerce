<?php

namespace Railroad\Ecommerce\Transformers;

use Doctrine\Common\Persistence\Proxy;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\Order;

class AddressTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = [];

    /**
     * @param Address $address
     *
     * @return array
     */
    public function transform(Address $address)
    {
        if (!empty($address->getUser())) {
            $this->defaultIncludes[] = 'user';
        } else {
            $this->defaultIncludes = array_diff($this->defaultIncludes, ['user']);
        }

        if (!empty($address->getCustomer())) {
            $this->defaultIncludes[] = 'customer';
        } else {
            $this->defaultIncludes = array_diff($this->defaultIncludes, ['customer']);
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

    /**
     * @param Address $address
     *
     * @return Item
     */
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

    /**
     * @param Address $address
     *
     * @return Item|null
     */
    public function includeCustomer(Address $address)
    {
        if (empty($address->getCustomer())) {
            return null;
        }

        if ($address->getCustomer() instanceof Proxy && !$address->getCustomer()->__isInitialized()) {
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
