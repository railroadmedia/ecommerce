<?php


namespace Railroad\Ecommerce\Decorators;

use Railroad\Ecommerce\Repositories\OrderItemRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\DecoratorInterface;

class OrderOrderItemsDecorators implements DecoratorInterface
{
    /**
     * @var \Railroad\Ecommerce\Repositories\OrderItemRepository
     */
    private $orderItemRepository;

    /**
     * OrderOrderItemsDecorators constructor.
     *
     * @param \Railroad\Ecommerce\Repositories\OrderItemRepository $orderItemRepository
     */
    public function __construct(OrderItemRepository $orderItemRepository)
    {
        $this->orderItemRepository = $orderItemRepository;
    }

    public function decorate($data)
    {
        $orderIds = $data->pluck('id');

        $orderItems = $this->orderItemRepository->query()
            ->whereIn(ConfigService::$tableOrderItem . '.order_id', $orderIds)
            ->get()
            ->keyBy('order_id');

        foreach ($data as $index => $orderItem) {
            if (isset($orderItems[$orderItem['id']])) {
                $data[$index]['items'] = (array)$orderItems[$orderItem['id']];
            }
        }

        return $data;
    }
}