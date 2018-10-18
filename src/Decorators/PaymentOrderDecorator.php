<?php

namespace Railroad\Ecommerce\Decorators;

use Illuminate\Database\DatabaseManager;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\DecoratorInterface;

class PaymentOrderDecorator implements DecoratorInterface
{
    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    private $orderRepository;

    public function __construct(DatabaseManager $databaseManager, OrderRepository $orderRepository)
    {
        $this->databaseManager = $databaseManager;
        $this->orderRepository = $orderRepository;
    }

    public function decorate($payments)
    {
        $paymentId = $payments->pluck('id');
        $orders =
            $this->orderRepository->query()
                ->join(
                    ConfigService::$tableOrderPayment,
                    ConfigService::$tableOrder . '.id',
                    '=',
                    ConfigService::$tableOrderPayment . '.order_id'
                )
                ->whereIn(ConfigService::$tableOrderPayment . '.payment_id', $paymentId)
                ->get()
                ->keyBy('payment_id');

        foreach ($payments as $index => $payment) {
            $payments[$index]['order'] = $orders[$payment['id']] ?? null;
        }

        return $payments;
    }
}