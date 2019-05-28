<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\Structures\DailyStatistic;
use Railroad\Ecommerce\Entities\Structures\ProductStatistic;
use Railroad\Ecommerce\Repositories\OrderRepository;

class StatsService
{
    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @param OrderRepository $orderRepository
     */
    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * Get daily statistics
     *
     * @param Request $request
     *
     * @return DailyStatistic[]
     */
    public function indexByRequest(Request $request): array
    {
        $results = [];
        $dateFormat = 'Y-m-d';

        $orders = $this->orderRepository->getOrdersForStats($request);

        foreach ($orders as $order) {
            $day = $order->getCreatedAt()->format($dateFormat);

            if (!isset($results[$day])) {
                $results[$day] = new DailyStatistic($day);
            }

            $results[$day] = $this->addOrderToDailyStatistic($results[$day], $order);
        }

        return array_values($results);
    }

    /**
     * @param Order $order
     * @param DailyStatistic $dailyStatistic
     *
     * @return DailyStatistic
     */
    public function addOrderToDailyStatistic(Order $order, DailyStatistic $dailyStatistic): DailyStatistic
    {
        $dailyStatistic->setTotalSales(
            round(
                $dailyStatistic->getTotalSales() + $order->getTotalPaid(),
                2
            )
        );

        $dailyStatistic->setTotalOrders($dailyStatistic->getTotalOrders() + 1);

        $productStatistics = $dailyStatistic->getProductStatistics();
        $day = $dailyStatistic->getDay();

        foreach ($order->getOrderItems() as $orderItem) {
            $product = $orderItem->getProduct();
            $currentProductStatistic = null;
            $currentProductStatisticId = $day . ':' . $product->getId();

            foreach ($productStatistics as $productStatistic) {
                if ($productStatistic->getId() == $currentProductStatisticId) {
                    $currentProductStatistic = $productStatistic;
                    break;
                }
            }

            if (!$currentProductStatistic) {
                $currentProductStatistic = new ProductStatistic(
                    $currentProductStatisticId,
                    $product->getSku()
                );

                $dailyStatistic->addProductStatistics($currentProductStatistic);
            }

            $currentProductStatistic->setTotalQuantitySold(
                $currentProductStatistic->getTotalQuantitySold()
                + $orderItem->getQuantity()
            );

            $currentProductStatistic->setTotalSales(
                $currentProductStatistic->getTotalSales()
                + $orderItem->getFinalPrice()
            );
        }

        return $dailyStatistic;
    }
}
