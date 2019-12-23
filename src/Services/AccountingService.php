<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\Structures\AccountingProduct;
use Railroad\Ecommerce\Entities\Structures\AccountingProductTotals;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\RefundRepository;
use Throwable;

class AccountingService
{
    /**
     * @var PaymentRepository
     */
    private $paymentRepository;

    /**
     * @var RefundRepository
     */
    private $refundRepository;

    /**
     * AccountingService constructor.
     *
     * @param PaymentRepository $paymentRepository
     * @param RefundRepository $refundRepository
     */
    public function __construct(
        PaymentRepository $paymentRepository,
        RefundRepository $refundRepository
    )
    {
        $this->paymentRepository = $paymentRepository;
        $this->refundRepository = $refundRepository;
    }

    /**
     * @param Request $request
     *
     * @return AccountingProductTotals
     *
     * @throws Throwable
     */
    public function indexByRequest(Request $request): AccountingProductTotals
    {
        $smallDate = $request->get(
            'small_date_time',
            Carbon::now()
                ->subDay()
                ->startOfDay()
                ->toDateTimeString()
        );

        $smallDateTime =
            Carbon::parse($smallDate)
                ->startOfDay();

        $bigDate = $request->get(
            'big_date_time',
            Carbon::now()
                ->subDay()
                ->endOfDay()
                ->toDateTimeString()
        );

        $brand = $request->get('brand');

        $bigDateTime =
            Carbon::parse($bigDate)
                ->endOfDay();

        $result = new AccountingProductTotals($smallDate, $bigDate);

        $totalTax = $this->paymentRepository->getPaymentsTaxPaid($smallDateTime, $bigDateTime, $brand);
        $totalTax = $totalTax ? round($totalTax, 2) : 0;

        $result->setTaxPaid($totalTax);

        $totalShipping = $this->paymentRepository->getPaymentsShippingPaid($smallDateTime, $bigDateTime, $brand);
        $totalShipping = $totalShipping ? round($totalShipping, 2) : 0;

        $result->setShippingPaid($totalShipping);

        $totalFinance = $this->paymentRepository->getPaymentsFinancePaid($smallDateTime, $bigDateTime, $brand);
        $totalFinance = $totalFinance ? round($totalFinance, 2) : 0;

        $result->setFinancePaid($totalFinance);

        $totalRefund = $this->refundRepository->getRefundPaid($smallDateTime, $bigDateTime, $brand);
        $totalRefund = $totalRefund ? round($totalRefund, 2) : 0;

        $result->setRefunded($totalRefund);

        $netProduct = $this->paymentRepository->getPaymentsNetProduct($smallDateTime, $bigDateTime, $brand);
        $netProduct = $netProduct ? round($netProduct, 2) : 0;

        $result->setNetProduct($netProduct);

        $netPaid = $this->paymentRepository->getPaymentsNetPaid($smallDateTime, $bigDateTime, $brand);
        $netPaid = $netPaid ? round($netPaid, 2) : 0;

        $result->setNetPaid($netPaid);

        // $start = microtime(true);
        $ordersProductsData = $this->paymentRepository->getOrdersProductsData($smallDateTime, $bigDateTime, $brand);
        // $totTime = microtime(true) - $start;

        // dd($totTime);

        $ordersMap = [];
        $productsMap = [];

        foreach ($ordersProductsData as $orderProductData) {
            if (!isset($ordersMap[$orderProductData['orderId']])) {
                $ordersMap[$orderProductData['orderId']] = [
                    'totalDue' => $orderProductData['totalDue'],
                    'productDue' => $orderProductData['productDue'],
                    'taxesDue' => $orderProductData['taxesDue'],
                    'shippingDue' => $orderProductData['shippingDue'],
                    'financeDue' => $orderProductData['financeDue'],
                    'totalPaid' => $orderProductData['totalPaid'],
                    'weight' => 0,
                    'items' => []
                ];
            }

            $ordersMap[$orderProductData['orderId']]['weight'] += $orderProductData['productWeight'];

            $ordersMap[$orderProductData['orderId']]['items'][$orderProductData['productId']] = [
                'quantity' => $orderProductData['quantity'],
                'finalPrice' => $orderProductData['finalPrice'],
                'productSku' => $orderProductData['productSku'],
                'productWeight' => $orderProductData['productWeight'],
            ];

            // todo - maybe fix the finalPrice
        }

        foreach ($ordersMap as $orderId => $orderData) {
            $taxRatio = $shippingRatio = $orderDueToPaidRatio = $totalDueForProducts = 0;

            if ($orderData['totalDue'] != 0 && $orderData['totalDue'] != null) {
                $taxRatio = $orderData['taxesDue'] ? $orderData['taxesDue'] / $orderData['totalDue'] : 0;
                $shippingRatio = $orderData['shippingDue'] ? $orderData['shippingDue'] / $orderData['totalDue'] : 0;
                $orderDueToPaidRatio = $orderData['totalPaid'] ? $orderData['totalPaid'] / $orderData['totalDue'] : 0;
                $totalDueForProducts = $orderData['totalDue'] - $orderData['taxesDue'] - $orderData['shippingDue'] - $orderData['financeDue'];
                $totalPaidForProducts = round($totalDueForProducts * $orderDueToPaidRatio, 2);
            }

            foreach ($orderData['items'] as $productId => $productData) {
                $thisOrdersItemOrderRatio = $totalDueForProducts ? $productData['finalPrice'] / $totalDueForProducts : 0;
                $taxForItem = $orderData['totalPaid'] * $taxRatio * $thisOrdersItemOrderRatio;
                $thisOrdersItemOrderWeightRatio = 1;

                if ($orderData['weight'] > 0) {
                    $thisOrdersItemOrderWeightRatio =
                        ($productData['productWeight'] ?? 0) / $orderData['weight'];
                }

                $shippingForItem = $orderData['totalPaid'] * $shippingRatio * $thisOrdersItemOrderWeightRatio;

                if (!isset($productsMap[$productId])) {
                    $productsMap[$productId] = [
                        'taxPaid' => 0,
                        'shippingPaid' => 0,
                    ];
                }

                $productsMap[$productId]['taxPaid'] += $taxForItem;
                $productsMap[$productId]['shippingPaid'] += $shippingForItem;
            }
        }

        // $start = microtime(true);
        $subscriptionsProductsData = $this->paymentRepository->getSubscriptionsProductsData($smallDateTime, $bigDateTime, $brand);
        // $totTime = microtime(true) - $start;

        // dd($totTime);

        foreach ($subscriptionsProductsData as $subscriptionProductData) {
            if (!isset($productsMap[$subscriptionProductData['productId']])) {
                $productsMap[$subscriptionProductData['productId']] = [
                    'taxPaid' => 0,
                    'shippingPaid' => 0,
                ];
            }

            $productsMap[$subscriptionProductData['productId']]['taxPaid'] += $subscriptionProductData['tax'];
        }

        // dd($ordersMap);
        // dd($ordersMap[121029]); // no tax, no shipping

        // dd($ordersMap[119541]); // has tax, has shipping
        // dd($ordersMap[119539]); // has tax, no shipping
        // dd($ordersMap[119545]); // no tax, has shipping

        dd($productsMap);

        // dd($totTime);

        return $result;
    }
}
