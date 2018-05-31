<?php

namespace Railroad\Ecommerce\Decorators;

use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\DecoratorInterface;

class SubscriptionProductDecorator implements DecoratorInterface
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function decorate($subscriptions)
    {
        $productIds = $subscriptions->pluck('product_id');

        $products = $this->productRepository->query()
            ->whereIn(ConfigService::$tableProduct . '.id', $productIds)
            ->get()
            ->keyBy('id');

        foreach ($subscriptions as $index => $subscription) {
            $subscriptions[$index]['product'] =
                ((array)$products[$subscription['product_id']]) ?? null;
        }

        return $subscriptions;
    }
}