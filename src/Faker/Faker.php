<?php

namespace Railroad\Ecommerce\Faker;

use Carbon\Carbon;
use Faker\Generator;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\ProductService;
use Railroad\Ecommerce\Services\SubscriptionService;

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
                'active'                      => 1,
                'description'                 => $this->text,
                'thumbnail_url'               => $this->imageUrl(),
                'is_physical'                 => $this->randomElement([0,1]),
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
}