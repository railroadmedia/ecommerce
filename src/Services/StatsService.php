<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\OrderPayment;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Structures\DailyStatistic;
use Railroad\Ecommerce\Entities\Structures\ProductStatistic;
use Railroad\Ecommerce\Entities\SubscriptionPayment;
use Railroad\Ecommerce\Repositories\OrderPaymentRepository;
use Railroad\Ecommerce\Repositories\RefundRepository;
use Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository;

class StatsService
{
    /**
     * @var OrderPaymentRepository
     */
    protected $orderPaymentRepository;

    /**
     * @var RefundRepository
     */
    protected $refundRepository;

    /**
     * @var SubscriptionPaymentRepository
     */
    protected $subscriptionPaymentRepository;

    /**
     * @param OrderPaymentRepository $orderPaymentRepository
     * @param RefundRepository $refundRepository
     * @param SubscriptionPaymentRepository $subscriptionPaymentRepository
     */
    public function __construct(
        OrderPaymentRepository $orderPaymentRepository,
        RefundRepository $refundRepository,
        SubscriptionPaymentRepository $subscriptionPaymentRepository
    ) {
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->refundRepository = $refundRepository;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
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

        $orderPayments = $this->orderPaymentRepository->getOrderPaymentsForStats($request);

        foreach ($orderPayments as $orderPayment) {
            if ($orderPayment->getPayment()->getStatus() == Payment::STATUS_FAILED) {
                continue;
            }

            $day = $orderPayment->getPayment()->getCreatedAt()->format($dateFormat);

            if (!isset($results[$day])) {
                $results[$day] = new DailyStatistic($day);
            }

            $results[$day] = $this->addOrderPaymentToDailyStatistic($orderPayment, $results[$day]);
        }

        $subscriptionPayments = $this->subscriptionPaymentRepository->getSubscriptionPaymentsForStats($request);

        foreach ($subscriptionPayments as $subscriptionPayment) {
            $day = $subscriptionPayment->getPayment()->getCreatedAt()->format($dateFormat);

            if (!isset($results[$day])) {
                $results[$day] = new DailyStatistic($day);
            }

            $results[$day] = $this->addSubscriptionPaymentToDailyStatistic($subscriptionPayment, $results[$day]);
        }

        $refunds = $this->refundRepository->getRefundsForStats($request);

        foreach ($refunds as $refund) {
            $day = $refund->getCreatedAt()->format($dateFormat);

            if (!isset($results[$day])) {
                $results[$day] = new DailyStatistic($day);
            }

            $dailyStatistic = $results[$day];

            $dailyStatistic->setTotalRefunded(
                round(
                    $dailyStatistic->getTotalRefunded() + $refund->getRefundedAmount(),
                    2
                )
            );
        }

        return array_values($results);
    }

    /**
     * @param OrderPayment $orderPayment
     * @param DailyStatistic $dailyStatistic
     *
     * @return DailyStatistic
     */
    public function addOrderPaymentToDailyStatistic(
        OrderPayment $orderPayment,
        DailyStatistic $dailyStatistic
    ): DailyStatistic
    {
        $payment = $orderPayment->getPayment();

        if ($payment->getStatus() != Payment::STATUS_FAILED) {

            $paymentAmountInBaseCurrency = round($payment->getTotalPaid() / $payment->getConversionRate(), 2);

            $dailyStatistic->setTotalSales(
                round(
                    $dailyStatistic->getTotalSales() + $paymentAmountInBaseCurrency,
                    2
                )
            );

            $dailyStatistic->setTotalOrders($dailyStatistic->getTotalOrders() + 1);

            $productStatistics = $dailyStatistic->getProductStatistics();
            $day = $dailyStatistic->getDay();
            $order = $orderPayment->getOrder();

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
                    $currentProductStatistic->getTotalQuantitySold() + $orderItem->getQuantity()
                );

                $currentProductStatistic->setTotalSales(
                    round(
                        $currentProductStatistic->getTotalSales() + $orderItem->getFinalPrice(),
                        2
                    )
                );
            }
        }

        return $dailyStatistic;
    }

    /**
     * @param SubscriptionPayment $subscriptionPayment
     * @param DailyStatistic $dailyStatistic
     *
     * @return DailyStatistic
     */
    public function addSubscriptionPaymentToDailyStatistic(
        SubscriptionPayment $subscriptionPayment,
        DailyStatistic $dailyStatistic
    ): DailyStatistic
    {
        $payment = $subscriptionPayment->getPayment();

        if ($payment->getStatus() != Payment::STATUS_FAILED) {
            $paymentAmountInBaseCurrency = round($payment->getTotalPaid() / $payment->getConversionRate(), 2);

            $dailyStatistic->setTotalSales(
                round(
                    $dailyStatistic->getTotalSales() + $paymentAmountInBaseCurrency,
                    2
                )
            );

            $dailyStatistic->setTotalSuccessfulRenewals($dailyStatistic->getTotalSuccessfulRenewals() + 1);
        }
        else {
            $dailyStatistic->setTotalFailedRenewals($dailyStatistic->getTotalFailedRenewals() + 1);
        }

        return $dailyStatistic;
    }
}
