<?php

namespace Railroad\Ecommerce\Decorators;

use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\DecoratorInterface;

class OrderItemProductDecorator implements DecoratorInterface
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function decorate($orderItems)
    {
        $productIds = $orderItems->pluck('product_id');

        $products = $this->productRepository->query()
            ->whereIn(ConfigService::$tableProduct . '.id', $productIds)
            ->get()
            ->keyBy('id');

        foreach ($orderItems as $index => $orderItem) {
            $orderItems[$index]['product'] =
                isset($products[$orderItem['product_id']]) ? (array) $products[$orderItem['product_id']] : null;
        }

        return $orderItems;
    }
}