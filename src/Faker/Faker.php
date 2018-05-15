<?php

namespace Railroad\Ecommerce\Faker;

use Carbon\Carbon;
use Faker\Generator;
use Railroad\Ecommerce\Services\AddressService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\ProductService;
use Railroad\Ecommerce\Services\SubscriptionService;
use Webpatser\Countries\Countries;

class Faker extends Generator
{
    public function product(array $override = [])
    {
        return array_merge(
            [
                'name'                        => $this->word,
                'sku'                         => $this->word,
                'price'                       => $this->numberBetween(1, 1000),
                'type'                        => $this->randomElement([
                    ProductService::TYPE_PRODUCT,
                    ProductService::TYPE_SUBSCRIPTION
                ]),
                'active'                      => $this->randomElement([0, 1]),
                'description'                 => $this->text,
                'thumbnail_url'               => $this->imageUrl(),
                'is_physical'                 => $this->randomElement([0, 1]),
                'weight'                      => $this->numberBetween(0, 100),
                'subscription_interval_type'  => $this->randomElement([
                    SubscriptionService::INTERVAL_TYPE_YEARLY,
                    SubscriptionService::INTERVAL_TYPE_MONTHLY,
                    SubscriptionService::INTERVAL_TYPE_DAILY
                ]),
                'subscription_interval_count' => $this->numberBetween(0, 12),
                'stock'                       => $this->numberBetween(1, 1000),
                'brand'                       => ConfigService::$brand,
                'created_on'                  => Carbon::now()->toDateTimeString()
            ],
            $override
        );
    }

    public function customer(array $override = [])
    {
        return array_merge(
            [
                'phone'      => $this->phoneNumber,
                'email'      => $this->email,
                'brand'      => ConfigService::$brand,
                'created_on' => Carbon::now()->toDateTimeString()
            ],
            $override
        );
    }

    public function address(array $override = [])
    {
        return array_merge(
            [
                'type'          => $this->randomElement([
                    AddressService::BILLING_ADDRESS,
                    AddressService::SHIPPING_ADDRESS
                ]),
                'brand'         => ConfigService::$brand,
                'user_id'       => rand(),
                'customer_id'   => null,
                'first_name'    => $this->firstName,
                'last_name'     => $this->lastName,
                'street_line_1' => $this->streetAddress,
                'street_line_2' => '',
                'city'          => $this->city,
                'zip'           => $this->postcode,
                'state'         => $this->word,
                'country'       => $this->randomElement(array_column(Countries::getCountries(), 'full_name')),
                'created_on'    => Carbon::now()->toDateTimeString()
            ],
            $override
        );
    }
}