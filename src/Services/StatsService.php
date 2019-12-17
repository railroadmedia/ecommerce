<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\Structures\DailyStatistic;
use Railroad\Ecommerce\Entities\Structures\ProductStatistic;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\RefundRepository;

class StatsService
{
    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @var RefundRepository
     */
    protected $refundRepository;

    /**
     * @param EcommerceEntityManager $entityManager
     * @param PaymentRepository $paymentRepository
     * @param RefundRepository $refundRepository
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        PaymentRepository $paymentRepository,
        RefundRepository $refundRepository
    ) {
        $this->entityManager = $entityManager;
        $this->paymentRepository = $paymentRepository;
        $this->refundRepository = $refundRepository;
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

            $add = false;

            $dailyOrdersProductsMap = [];

            $day = $currentDay->format($dateFormat);

            $dailyStatistic = new DailyStatistic($day);

            $totalSales = $this->paymentRepository->getDailyTotalSalesStats($currentDay, $brand);

            if ($totalSales) {
                $add = true;
            }

            $dailyStatistic->setTotalSales($totalSales ?? 0);

            $totalOrders = $this->paymentRepository->getDailyTotalOrdersStats($currentDay, $brand);

            if ($totalOrders) {
                $add = true;
            }

            $dailyStatistic->setTotalOrders($totalOrders ?? 0);

            $totalRenewal = $this->paymentRepository->getDailyTotalSalesFromRenewals($currentDay, $brand);

            if ($totalRenewal) {
                $add = true;
            }

            $dailyStatistic->setTotalSalesFromRenewals($totalRenewal ?? 0);

            $totalSuccessfulRenewal = $this->paymentRepository->getDailyTotalSuccessfulRenewals($currentDay, $brand);

            if ($totalSuccessfulRenewal) {
                $add = true;
            }

            $dailyStatistic->setTotalSuccessfulRenewals($totalSuccessfulRenewal ?? 0);

            $totalFailedRenewal = $this->paymentRepository->getDailyTotalFailedRenewals($currentDay, $brand);

            if ($totalFailedRenewal) {
                $add = true;
            }

            $dailyStatistic->setTotalFailedRenewals($totalFailedRenewal ?? 0);

            $totalRefunds = $this->refundRepository->getDailyTotalRefunds($currentDay, $brand);

            if ($totalRefunds) {
                $add = true;
            }

            $dailyStatistic->setTotalRefunded($totalRefunds ?? 0);

            $dailyOrdersProductsData = $this->paymentRepository->getDailyOrdersProductStatistic($currentDay, $brand);

            if (!empty($dailyOrdersProductsData)) {
                $add = true;
            }

            foreach ($dailyOrdersProductsData as $productData) {
                $currentProductStatistic = null;
                $currentProductStatisticId = $day . ':' . $productData['id'];

                if (!isset($dailyOrdersProductsMap[$currentProductStatisticId])) {
                    $currentProductStatistic = new ProductStatistic(
                        $currentProductStatisticId,
                        $productData['sku']
                    );

                    $dailyStatistic->addProductStatistics($currentProductStatistic);

                    $dailyOrdersProductsMap[$currentProductStatisticId] = $currentProductStatistic;
                } else {
                    $currentProductStatistic = $dailyOrdersProductsMap[$currentProductStatisticId];
                }

                $currentProductStatistic->setTotalQuantitySold(
                    $currentProductStatistic->getTotalQuantitySold() + $productData['sold']
                );

                $currentProductStatistic->setTotalSales(
                    round(
                        $currentProductStatistic->getTotalSales() + $productData['sales'],
                        2
                    )
                );
            }

            $dailySubscriptionsProductsData = $this->paymentRepository->getDailySubscriptionsProductStatistic(
                $currentDay,
                $brand
            );

            if (!empty($dailySubscriptionsProductsData)) {
                $add = true;
            }

            foreach ($dailySubscriptionsProductsData as $productData) {
                $currentProductStatistic = null;
                $currentProductStatisticId = $day . ':' . $productData['id'];

                if (!isset($dailyOrdersProductsMap[$currentProductStatisticId])) {
                    $currentProductStatistic = new ProductStatistic(
                        $currentProductStatisticId,
                        $productData['sku']
                    );

                    $dailyStatistic->addProductStatistics($currentProductStatistic);

                    $dailyOrdersProductsMap[$currentProductStatisticId] = $currentProductStatistic;
                } else {
                    $currentProductStatistic = $dailyOrdersProductsMap[$currentProductStatisticId];
                }

                $currentProductStatistic->setTotalRenewalSales(
                    round(
                        $currentProductStatistic->getTotalRenewalSales() + $productData['sales'],
                        2
                    )
                );

                $currentProductStatistic->setTotalRenewals(
                    $currentProductStatistic->getTotalRenewals() + $productData['sold']
                );
            }

            if ($add) {
                $results[$day] = $dailyStatistic;
            }

            $dailyOrdersProductsMap = null;

            $this->entityManager->clear();

            $currentDay->addDay();
        }

        return array_reverse(array_values($results));
    }
}
