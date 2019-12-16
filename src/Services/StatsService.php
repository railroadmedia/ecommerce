<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\Structures\DailyStatistic;
use Railroad\Ecommerce\Entities\Structures\ProductStatistic;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\RefundRepository;

class StatsService
{
    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @var RefundRepository
     */
    protected $refundRepository;

    /**
     * @param PaymentRepository $paymentRepository
     * @param RefundRepository $refundRepository
     */
    public function __construct(
        PaymentRepository $paymentRepository,
        RefundRepository $refundRepository
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->refundRepository = $refundRepository;
    }

    public function newIndexByRequest(Request $request): array
    {
        $results = [];
        $dateFormat = 'Y-m-d';

        $smallDate = $request->get(
            'small_date_time',
            Carbon::now()
                ->subDay()
                ->toDateTimeString()
        );

        $smallDateTime =
            Carbon::parse($smallDate)
                ->startOfDay();

        $bigDate = $request->get(
            'big_date_time',
            Carbon::now()
                ->subDay()
                ->toDateTimeString()
        );

        $bigDateTime =
            Carbon::parse($bigDate)
                ->endOfDay();

        if ($smallDateTime > $bigDateTime) {
            $tmp = $bigDate;

            $bigDate = $smallDateTime;

            $smallDateTime = $tmp;
        }

        $currentDay = $smallDateTime->copy();

        $brand = $request->get('brand');

        while($currentDay < $bigDate) {

            $day = $currentDay->format($dateFormat);

            $dailyStatistic = new DailyStatistic($day);

            $totalSales = $this->paymentRepository->getDailyTotalSalesStats($currentDay, $brand);

            $dailyStatistic->setTotalSales($totalSales ?? 0);

            $totalOrders = $this->paymentRepository->getDailyTotalOrdersStats($currentDay, $brand);

            $dailyStatistic->setTotalOrders($totalOrders ?? 0);

            $totalRenewal = $this->paymentRepository->getDailyTotalSalesFromRenewals($currentDay, $brand);

            $dailyStatistic->setTotalSalesFromRenewals($totalRenewal ?? 0);

            $results[$day] = $dailyStatistic;

            $currentDay->addDay();
        }

        return $results;
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

        $payments = $this->paymentRepository->getPaymentsForStats($request);

        foreach ($payments as $payment) {

            $day = $payment->getCreatedAt()->format($dateFormat);

            $dailyStatistic = null;

            if (isset($results[$day])) {
                $dailyStatistic = $results[$day];
            }
            else {
                $dailyStatistic = new DailyStatistic($day);
            }

            if ($payment->getOrderPayment() && $payment->getType() == Payment::TYPE_INITIAL_ORDER) {
                $dailyStatistic = $this->addOrderPaymentToDailyStatistic($payment, $dailyStatistic);
            }

            if ($payment->getSubscriptionPayment() && $payment->getType() == Payment::TYPE_SUBSCRIPTION_RENEWAL) {
                $dailyStatistic = $this->addSubscriptionPaymentToDailyStatistic($payment, $dailyStatistic);
            }

            if ($payment->getStatus() != Payment::STATUS_FAILED) {
                $paymentAmountInBaseCurrency = round($payment->getTotalPaid() / $payment->getConversionRate(), 2);

                $dailyStatistic->setTotalSales(
                    round(
                        $dailyStatistic->getTotalSales() + $paymentAmountInBaseCurrency,
                        2
                    )
                );
            }

            $results[$day] = $dailyStatistic;
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

        return array_reverse(array_values($results));
    }

    /**
     * @param Payment $payment
     * @param DailyStatistic $dailyStatistic
     *
     * @return DailyStatistic
     */
    public function addOrderPaymentToDailyStatistic(
        Payment $payment,
        DailyStatistic $dailyStatistic
    ): DailyStatistic
    {
        if ($payment->getStatus() != Payment::STATUS_FAILED) {

            $dailyStatistic->setTotalOrders($dailyStatistic->getTotalOrders() + 1);

            $productStatistics = $dailyStatistic->getProductStatistics();
            $day = $dailyStatistic->getDay();
            $order = $payment->getOrderPayment()->getOrder();

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
     * @param Payment $payment
     * @param DailyStatistic $dailyStatistic
     *
     * @return DailyStatistic
     */
    public function addSubscriptionPaymentToDailyStatistic(
        Payment $payment,
        DailyStatistic $dailyStatistic
    ): DailyStatistic
    {
        if ($payment->getStatus() != Payment::STATUS_FAILED) {
            $dailyStatistic->setTotalSuccessfulRenewals($dailyStatistic->getTotalSuccessfulRenewals() + 1);

            $subscription = $payment->getSubscriptionPayment()->getSubscription();

            if ($subscription->getTotalCyclesPaid() > 1 && $subscription->getType() != Subscription::TYPE_PAYMENT_PLAN) {
                $sales = round(
                    $subscription->getTotalPrice() + $dailyStatistic->getTotalSalesFromRenewals(),
                    2
                );

                $dailyStatistic->setTotalSalesFromRenewals($sales);

                $productStatistics = $dailyStatistic->getProductStatistics();
                $day = $dailyStatistic->getDay();

                $product = $subscription->getProduct();
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

                $currentProductStatistic->setTotalRenewalSales(
                    round($currentProductStatistic->getTotalRenewalSales() + $subscription->getTotalPrice(), 2)
                );

                $currentProductStatistic->setTotalRenewals($currentProductStatistic->getTotalRenewals() + 1);
            }
        }
        else {
            $dailyStatistic->setTotalFailedRenewals($dailyStatistic->getTotalFailedRenewals() + 1);
        }

        return $dailyStatistic;
    }
}
