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

        $ordersProductsData = $this->paymentRepository->getOrdersProductsData($smallDateTime, $bigDateTime, $brand);

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
                'productName' => $orderProductData['productName'],
                'productWeight' => $orderProductData['productWeight'],
            ];

            // todo - maybe fix the finalPrice
        }

        foreach ($ordersMap as $orderId => $orderData) {

            $totalForPhysical = 0;

            foreach ($orderData['items'] as $productId => $productData) {

                $oderItemRatio = 0;

                if ($orderData['totalDue'] != 0 && $orderData['totalDue'] != null && $productData['finalPrice']) {
                    $oderItemRatio = $productData['finalPrice'] / $orderData['totalDue'];
                }

                if (!isset($productsMap[$productId])) {
                    $productsMap[$productId] = [
                        'taxPaid' => 0,
                        'shippingPaid' => 0,
                        'financePaid' => 0,
                        'netProduct' => 0,
                        'netPaid' => 0,
                        'productSku' => $productData['productSku'],
                        'productName' => $productData['productName'],
                        'refunded' => 0,
                        'quantity' => 0,
                        'refundedQuantity' => 0,
                        'freeQuantity' => 0,
                    ];
                }

                $tax = $shipping = $finance = 0;

                $tax = $oderItemRatio * $orderData['taxesDue'];
                $productsMap[$productId]['taxPaid'] += $tax;

                if ((float)$productData['productWeight'] > 0) {
                    $totalForPhysical += $productData['finalPrice'];
                }

                if ((float)$orderData['financeDue'] > 0) {
                    $finance = $oderItemRatio * $orderData['financeDue'];
                    $productsMap[$productId]['financePaid'] += $finance;
                }

                $productsMap[$productId]['netProduct'] += $productData['finalPrice'];
                $productsMap[$productId]['netPaid'] += $productData['finalPrice'] + $tax + $shipping + $finance;
                $productsMap[$productId]['quantity'] += $productData['quantity'];

                if ($productData['finalPrice'] == 0) {
                    $productsMap[$productId]['freeQuantity'] += $productData['quantity'];
                }
            }

            if ((float)$orderData['shippingDue'] > 0 && $totalForPhysical) {
                foreach ($orderData['items'] as $productId => $productData) {
                    if ((float)$productData['finalPrice'] > 0 && (float)$productData['productWeight'] > 0) {
                        $oderItemRatio = $productData['finalPrice'] / $totalForPhysical;
                        $shipping = $oderItemRatio * $orderData['shippingDue'];
                        $productsMap[$productId]['shippingPaid'] += $shipping;
                    }
                }
            }
        }

        $ordersMap = null;

        $subscriptionsProductsData = $this->paymentRepository->getSubscriptionsProductsData($smallDateTime, $bigDateTime, $brand);


        foreach ($subscriptionsProductsData as $subscriptionProductData) {
            if (!isset($productsMap[$subscriptionProductData['productId']])) {
                $productsMap[$subscriptionProductData['productId']] = [
                    'taxPaid' => 0,
                    'shippingPaid' => 0,
                    'financePaid' => 0,
                    'netProduct' => 0,
                    'netPaid' => 0,
                    'productSku' => $subscriptionProductData['productSku'],
                    'productName' => $subscriptionProductData['productName'],
                    'refunded' => 0,
                    'quantity' => 0,
                    'refundedQuantity' => 0,
                    'freeQuantity' => 0,
                ];
            }

            $productId = $subscriptionProductData['productId'];
            $productsMap[$productId]['taxPaid'] += $subscriptionProductData['tax'];
            $productsMap[$productId]['netProduct'] += $subscriptionProductData['totalPrice'] - $subscriptionProductData['tax'];
            $productsMap[$productId]['netPaid'] += $subscriptionProductData['totalPrice'];
            $productsMap[$productId]['quantity'] += 1;

        }

        $refundOrdersProductsData = $this->refundRepository->getAccountingOrderProductsData($smallDateTime, $bigDateTime, $brand);

        $refundOrdersMap = [];

        foreach ($refundOrdersProductsData as $refundOrdersProductData) {
            if (!isset($refundOrdersMap[$refundOrdersProductData['orderId']])) {
                $refundOrdersMap[$refundOrdersProductData['orderId']] = [
                    'totalDue' => $refundOrdersProductData['totalDue'],
                    'refundedAmount' => $refundOrdersProductData['refundedAmount'],
                    'productDue' => $refundOrdersProductData['productDue'],
                    'taxesDue' => $refundOrdersProductData['taxesDue'],
                    'shippingDue' => $refundOrdersProductData['shippingDue'],
                    'financeDue' => $refundOrdersProductData['financeDue'],
                    'totalPaid' => $refundOrdersProductData['totalPaid'],
                    'weight' => 0,
                    'items' => []
                ];
            }

            $refundOrdersMap[$refundOrdersProductData['orderId']]['weight'] += $refundOrdersProductData['productWeight'];

            $refundOrdersMap[$refundOrdersProductData['orderId']]['items'][$refundOrdersProductData['productId']] = [
                'quantity' => $refundOrdersProductData['quantity'],
                'finalPrice' => $refundOrdersProductData['finalPrice'],
                'productSku' => $refundOrdersProductData['productSku'],
                'productName' => $refundOrdersProductData['productName'],
                'productWeight' => $refundOrdersProductData['productWeight'],
            ];
        }

        foreach ($refundOrdersMap as $orderId => $refundOrderData) {

            foreach ($refundOrderData['items'] as $productId => $productData) {

                $oderItemRatio = 0;

                if ($refundOrderData['totalDue'] != 0 && $refundOrderData['totalDue'] != null && $productData['finalPrice']) {
                    $oderItemRatio = $productData['finalPrice'] / $refundOrderData['totalDue'];
                }

                if (!isset($productsMap[$productId])) {
                    $productsMap[$productId] = [
                        'taxPaid' => 0,
                        'shippingPaid' => 0,
                        'financePaid' => 0,
                        'netProduct' => 0,
                        'netPaid' => 0,
                        'productSku' => $productData['productSku'],
                        'productName' => $productData['productName'],
                        'refunded' => 0,
                        'quantity' => 0,
                        'refundedQuantity' => 0,
                        'freeQuantity' => 0,
                    ];
                }

                $productsMap[$productId]['refunded'] += $oderItemRatio * $refundOrderData['refundedAmount'];
                $productsMap[$productId]['quantity'] -= $productData['quantity'];
                $productsMap[$productId]['refundedQuantity'] += $productData['quantity'];
            }
        }

        $refundOrdersMap = null;

        $refundSubscriptionsProductsData = $this->refundRepository->getAccountingSubscriptionProductsData($smallDateTime, $bigDateTime, $brand);

        $refundSubscriptionsMap = [];

        foreach ($refundSubscriptionsProductsData as $refundProductData) {
            if (!isset($productsMap[$subscriptionProductData['productId']])) {
                $productsMap[$subscriptionProductData['productId']] = [
                    'taxPaid' => 0,
                    'shippingPaid' => 0,
                    'financePaid' => 0,
                    'netProduct' => 0,
                    'netPaid' => 0,
                    'productSku' => $subscriptionProductData['productSku'],
                    'productName' => $subscriptionProductData['productName'],
                    'refunded' => 0,
                    'quantity' => 0,
                    'refundedQuantity' => 0,
                    'freeQuantity' => 0,
                ];
            }

            $productId = $refundProductData['productId'];
            $productsMap[$productId]['refunded'] += $refundProductData['refundedAmount'];
            $productsMap[$productId]['quantity'] -= 1;
            $productsMap[$productId]['refundedQuantity'] += 1;
        }

        foreach ($productsMap as $productId => $productData) {

            $productStatistics = new AccountingProduct($productId);

            $productStatistics->setName($productData['productName']);
            $productStatistics->setSku($productData['productSku']);
            $productStatistics->setTaxPaid(round($productData['taxPaid'], 2));
            $productStatistics->setShippingPaid(round($productData['shippingPaid'], 2));
            $productStatistics->setFinancePaid(round($productData['financePaid'], 2));
            $productStatistics->setLessRefunded(round($productData['refunded'], 2));
            $productStatistics->setTotalQuantity($productData['quantity']);
            $productStatistics->setRefundedQuantity($productData['refundedQuantity']);
            $productStatistics->setFreeQuantity($productData['freeQuantity']);
            $productStatistics->setNetProduct(round($productData['netProduct'], 2));
            $productStatistics->setNetPaid(round($productData['netPaid'], 2));

            $result->addProductStatistics($productStatistics);
        }

        return $result;
    }
}
