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
        $orderItems =
            $this->orderItemRepository->query()
                ->whereIn(ConfigService::$tableOrderItem . '.order_id', $orderIds)
                ->get();
        foreach ($data as $index => $order) {
            foreach ($orderItems as $orderItemIndex => $orderItem) {
                if ($orderItem['order_id'] == $order['id']) {
                    $data[$index]['items'][$orderItem['id']] = $orderItem;
                }
            }
        }
        return $data;
    }
}
