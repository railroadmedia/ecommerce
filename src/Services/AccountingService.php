<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\AccountingProduct;
use Railroad\Ecommerce\Entities\Structures\AccountingProductTotals;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
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
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * AccountingService constructor.
     *
     * @param PaymentRepository $paymentRepository
     * @param RefundRepository $refundRepository
     * @param ProductRepository $productRepository
     */
    public function __construct(
        PaymentRepository $paymentRepository,
        RefundRepository $refundRepository,
        ProductRepository $productRepository
    )
    {
        $this->paymentRepository = $paymentRepository;
        $this->refundRepository = $refundRepository;
        $this->productRepository = $productRepository;
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
                ->subWeek()
                ->toDateTimeString()
        );

        $smallDateTime =
            Carbon::parse($smallDate, config('ecommerce.accounting_report_timezone', 'UTC'))
                ->startOfDay()
                ->timezone('UTC');


        $bigDate = $request->get(
            'big_date_time',
            Carbon::now()
                ->toDateTimeString()
        );

        $brand = $request->get('brand');

        $bigDateTime =
            Carbon::parse($bigDate, config('ecommerce.accounting_report_timezone', 'UTC'))
                ->endOfDay()
                ->timezone('UTC');

        // fetch report summary totals, calculated atleast partially in mysql
        $result = new AccountingProductTotals($smallDate, $bigDate);

        $totalTax = $this->paymentRepository->getPaymentsTaxPaid($smallDateTime, $bigDateTime, $brand);
        $totalTaxRounded = $totalTax ? round($totalTax, 2) : 0;

        $result->setTaxPaid($totalTaxRounded);

        $totalShipping = $this->paymentRepository->getPaymentsShippingPaid($smallDateTime, $bigDateTime, $brand);
        $totalShippingRounded = $totalShipping ? round($totalShipping, 2) : 0;

        $result->setShippingPaid($totalShippingRounded);

        $totalFinance = $this->paymentRepository->getPaymentsFinancePaid($smallDateTime, $bigDateTime, $brand);
        $totalFinanceRounded = $totalFinance ? round($totalFinance, 2) : 0;

        $result->setFinancePaid($totalFinanceRounded);

        $totalRefund = $this->refundRepository->getRefundPaid($smallDateTime, $bigDateTime, $brand);
        $totalRefundRounded = $totalRefund ? round($totalRefund, 2) : 0;

        $result->setRefunded($totalRefundRounded);

        $netProduct = $this->paymentRepository->getPaymentsNetProduct($smallDateTime, $bigDateTime, $brand);
        $netProductRounded = $netProduct ? round($netProduct, 2) : 0;

        $result->setNetProduct($netProductRounded);

        // sum up mysql calculated sub-totals, minus refunds
        $netPaid = $totalTax + $totalShipping + $totalFinance + $netProduct - $totalRefund;
        $netPaidRounded = $netPaid ? round($netPaid, 2) : 0;

        $result->setNetPaid($netPaidRounded);

        // fetch orders data
        $ordersProductsData = $this->paymentRepository->getOrdersProductsData($smallDateTime, $bigDateTime, $brand);

        $ordersMap = [];
        $productsMap = [];

        // restructure mysql result data into groups of order items
        foreach ($ordersProductsData as $orderProductData) {
            if (!isset($ordersMap[$orderProductData['orderId']])) {
                $ordersMap[$orderProductData['orderId']] = [
                    'totalDue' => $orderProductData['totalDue'],
                    'productDue' => 0,
                    'taxesDue' => $orderProductData['taxesDue'],
                    'shippingDue' => $orderProductData['shippingDue'],
                    'financeDue' => $orderProductData['financeDue'],
                    'totalPaid' => $orderProductData['totalPaid'],
                    'items' => [],
                ];
            }

            // some orders have NULL productDue in mysql, thus order items sum is recalculated
            $ordersMap[$orderProductData['orderId']]['productDue'] += $orderProductData['finalPrice'];

            $ordersMap[$orderProductData['orderId']]['items'][$orderProductData['productId']] = [
                'quantity' => $orderProductData['quantity'],
                'finalPrice' => $orderProductData['finalPrice'],
                'productSku' => $orderProductData['productSku'],
                'productName' => $orderProductData['productName'],
                'productType' => $orderProductData['productType'],
            ];
        }

        // for each group of order items, start calculating product stats
        foreach ($ordersMap as $orderId => $orderData) {

            $paidPhysical = [];
            $freePhysical = [];

            foreach ($orderData['items'] as $productId => $productData) {

                $oderItemRatio = 0;

                if (
                    $orderData['productDue'] != 0
                    && $orderData['productDue'] != null
                    && $productData['finalPrice']
                ) {
                    $oderItemRatio = $productData['finalPrice'] / $orderData['productDue'];
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

                $tax = $finance = 0;

                $tax = $oderItemRatio * $orderData['taxesDue'];
                $productsMap[$productId]['taxPaid'] += $tax;

                if ((float)$orderData['shippingDue'] > 0 && $productData['productType'] == Product::TYPE_PHYSICAL_ONE_TIME) {
                    if ((float)$productData['finalPrice'] > 0) {
                        $paidPhysical[] = $productId;
                    } else {
                        $freePhysical[] = $productId;
                    }
                }

                if ((float)$orderData['financeDue'] > 0) {
                    $finance = $oderItemRatio * $orderData['financeDue'];
                    $productsMap[$productId]['financePaid'] += $finance;
                }

                $productsMap[$productId]['netProduct'] += $productData['finalPrice'];
                $productsMap[$productId]['netPaid'] += $productData['finalPrice'] + $tax + $finance;
                $productsMap[$productId]['quantity'] += $productData['quantity'];

                if ($productData['finalPrice'] == 0) {
                    $productsMap[$productId]['freeQuantity'] += $productData['quantity'];
                }
            }

            if ((float)$orderData['shippingDue'] > 0) {
                $shippingProducts = empty($paidPhysical) ? $freePhysical : $paidPhysical;

                if (count($shippingProducts) > 0) {
                    $shippingPerProduct = (float)$orderData['shippingDue'] / count($shippingProducts);

                    foreach ($shippingProducts as $productId) {
                        $productsMap[$productId]['shippingPaid'] += $shippingPerProduct;
                        $productsMap[$productId]['netPaid'] += $shippingPerProduct;
                    }
                }
            }
        }

        $ordersMap = $ordersProductsData = null;

        // fetch payment plans data
        $paymentPlansProductsData = $this->paymentRepository->getPaymentPlansProductsData($smallDateTime, $bigDateTime, $brand);

        $ordersMap = [];

        // restructure mysql result data into groups of order items
        foreach ($paymentPlansProductsData as $orderProductData) {
            if (!isset($ordersMap[$orderProductData['orderId']])) {
                $ordersMap[$orderProductData['orderId']] = [
                    'productDue' => 0,
                    'totalPrice' => $orderProductData['totalPrice'],
                    'tax' => $orderProductData['tax'],
                    'items' => [],
                ];
            }

            // some orders have NULL productDue in mysql, thus order items sum is recalculated
            $ordersMap[$orderProductData['orderId']]['productDue'] += $orderProductData['finalPrice'];

            $ordersMap[$orderProductData['orderId']]['items'][$orderProductData['productId']] = [
                'finalPrice' => $orderProductData['finalPrice'],
                'productSku' => $orderProductData['productSku'],
                'productName' => $orderProductData['productName'],
            ];
        }

        // for each group of order items, add product stats from payment plan
        foreach ($ordersMap as $orderId => $orderData) {

            foreach ($orderData['items'] as $productId => $productData) {

                $oderItemRatio = 0;

                if (
                    $orderData['productDue'] != 0
                    && $orderData['productDue'] != null
                    && $productData['finalPrice']
                ) {
                    $oderItemRatio = $productData['finalPrice'] / $orderData['productDue'];
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

                $paidForOrderItem = ($orderData['totalPrice'] - $orderData['tax']) * $oderItemRatio;

                $productsMap[$productId]['netProduct'] += $paidForOrderItem;
                $productsMap[$productId]['netPaid'] += $paidForOrderItem;

                // quantity data for payment plans is added only on initial order payment
            }
        }

        $ordersMap = $paymentPlansProductsData = null;

        // fetch renewed subscriptions data
        $subscriptionsProductsData = $this->paymentRepository->getSubscriptionsProductsData($smallDateTime, $bigDateTime, $brand);

        // add subscription's related product data
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

        $subscriptionsProductsData = null;

        $refundOrdersProductsData = $this->refundRepository->getAccountingOrderProductsData($smallDateTime, $bigDateTime, $brand);

        $refundOrdersMap = [];

        foreach ($refundOrdersProductsData as $refundOrdersProductData) {
            $orderId = $refundOrdersProductData['orderId'];
            $refundId = $refundOrdersProductData['refundId'];
            if (!isset($refundOrdersMap[$orderId])) {
                $refundOrdersMap[$orderId] = [
                    'firstRefundId' => $refundId,
                    'totalDue' => $refundOrdersProductData['totalDue'],
                    'productDue' => 0,
                    'taxesDue' => $refundOrdersProductData['taxesDue'],
                    'shippingDue' => $refundOrdersProductData['shippingDue'],
                    'financeDue' => $refundOrdersProductData['financeDue'],
                    'totalPaid' => $refundOrdersProductData['totalPaid'],
                    'weight' => 0,
                    'items' => [],
                    'hasPaidItems' => false,
                    'refunds' => []
                ];
            }

            // some orders have NULL productDue in mysql, thus order items sum is recalculated
            if ($refundId == $refundOrdersMap[$orderId]['firstRefundId']) {
                // an order may have multiple refunds, the if condition ensures correct productDue calculus
                $refundOrdersMap[$orderId]['productDue'] += $refundOrdersProductData['finalPrice'];
            }

            if ((float)$refundOrdersProductData['finalPrice'] > 0) {
                $refundOrdersMap[$orderId]['hasPaidItems'] = true;
            }

            if (!isset($refundOrdersMap[$orderId]['refunds'][$refundId])) {
                $refundOrdersMap[$orderId]['refunds'][$refundId] = $refundOrdersProductData['refundedAmount'];
            }

            $refundOrdersMap[$orderId]['items'][$refundOrdersProductData['productId']] = [
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

                if ($refundOrderData['hasPaidItems']) {
                    if (
                        $refundOrderData['productDue'] != 0
                        && $refundOrderData['productDue'] != null
                        && $productData['finalPrice']
                    ) {
                        $oderItemRatio = $productData['finalPrice'] / $refundOrderData['productDue'];
                    }
                } else {
                    $orderItemsCount = count($refundOrderData['items']) > 0 ? count($refundOrderData['items']) : 1;
                    $oderItemRatio = 1 / $orderItemsCount;
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

                $refundQuantityProcessed = false;

                foreach ($refundOrderData['refunds'] as $refundId => $refundedAmount) {
                    $refundedForProduct = $oderItemRatio * $refundedAmount;
                    $productsMap[$productId]['refunded'] += $refundedForProduct;
                    $productsMap[$productId]['netPaid'] -= $refundedForProduct;

                    if (!$refundQuantityProcessed) {
                        // process refund quantity only once, even on multiple refunds
                        $refundQuantityProcessed = true;
                        $productsMap[$productId]['quantity'] -= $productData['quantity'];
                        $productsMap[$productId]['refundedQuantity'] += $productData['quantity'];
                    }
                }
            }
        }

        $refundOrdersMap = $refundOrdersProductsData = null;

        $refundSubscriptionsProductsData = $this->refundRepository->getAccountingSubscriptionProductsData($smallDateTime, $bigDateTime, $brand);

        $refundSubscriptionsMap = [];

        foreach ($refundSubscriptionsProductsData as $refundProductData) {
            if (!isset($productsMap[$refundProductData['productId']])) {
                $productsMap[$refundProductData['productId']] = [
                    'taxPaid' => 0,
                    'shippingPaid' => 0,
                    'financePaid' => 0,
                    'netProduct' => 0,
                    'netPaid' => 0,
                    'productSku' => $refundProductData['productSku'],
                    'productName' => $refundProductData['productName'],
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
            $productsMap[$productId]['netPaid'] -= $refundProductData['refundedAmount'];
        }

        $refundSubscriptionsProductsData = $refundSubscriptionsMap = null;

        $refundPaymentPlansProductsData = $this->refundRepository->getAccountingPaymentPlansProductsData($smallDateTime, $bigDateTime, $brand);

        $refundOrdersMap = [];

        foreach ($refundPaymentPlansProductsData as $refundOrdersProductData) {
            $orderId = $refundOrdersProductData['orderId'];
            $refundId = $refundOrdersProductData['refundId'];
            if (!isset($refundOrdersMap[$orderId])) {
                $refundOrdersMap[$orderId] = [
                    'firstRefundId' => $refundId,
                    'productDue' => 0,
                    'totalPrice' => $refundOrdersProductData['totalPrice'],
                    'refundedAmount' => $refundOrdersProductData['refundedAmount'],
                    'items' => [],
                    'hasPaidItems' => false,
                    'refunds' => []
                ];
            }

            if ((float)$refundOrdersProductData['finalPrice'] > 0) {
                $refundOrdersMap[$refundOrdersProductData['orderId']]['hasPaidItems'] = true;
            }

            // some orders have NULL productDue in mysql, thus order items sum is recalculated
            if ($refundId == $refundOrdersMap[$orderId]['firstRefundId']) {
                // an order may have multiple refunds, the if condition ensures correct productDue calculus
                $refundOrdersMap[$orderId]['productDue'] += $refundOrdersProductData['finalPrice'];
            }

            if (!isset($refundOrdersMap[$orderId]['refunds'][$refundId])) {
                $refundOrdersMap[$orderId]['refunds'][$refundId] = $refundOrdersProductData['refundedAmount'];
            }

            $refundOrdersMap[$orderId]['items'][$refundOrdersProductData['productId']] = [
                'finalPrice' => $refundOrdersProductData['finalPrice'],
                'productSku' => $refundOrdersProductData['productSku'],
                'productName' => $refundOrdersProductData['productName'],
            ];
        }

        foreach ($refundOrdersMap as $orderId => $refundOrderData) {

            foreach ($refundOrderData['items'] as $productId => $productData) {

                $oderItemRatio = 0;

                if ($refundOrderData['hasPaidItems']) {
                    if (
                        $refundOrderData['productDue'] != 0
                        && $refundOrderData['productDue'] != null
                        && $productData['finalPrice']
                    ) {
                        $oderItemRatio = $productData['finalPrice'] / $refundOrderData['productDue'];
                    }
                } else {
                    $orderItemsCount = count($refundOrderData['items']) > 0 ? count($refundOrderData['items']) : 1;
                    $oderItemRatio = 1 / $orderItemsCount;
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

                foreach ($refundOrderData['refunds'] as $refundId => $refundedAmount) {
                    $refundedForProduct = $oderItemRatio * $refundedAmount;
                    $productsMap[$productId]['refunded'] += $refundedForProduct;
                    $productsMap[$productId]['netPaid'] -= $refundedForProduct;
                }

                // quantity data for payment plans refunds is considered only on initial order payment
            }
        }

        $refundOrdersMap = $refundPaymentPlansProductsData = null;

        foreach ($productsMap as $productId => $productData) {

            if (empty($productData['productName'])) {
                continue;
            }

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

        // accounting also needs rows with all 0's for products without data
        $allProducts = $this->productRepository->all();

        // if brand is passed in, only add products from that brand
        foreach ($allProducts as $productIndex => $product) {
            if (!empty($brand) && $product->getBrand() != $brand) {
                continue;
            }

            if (empty($result->getAccountingProducts()[$product->getId()])) {
                $productStatistics = new AccountingProduct($product->getId());

                $productStatistics->setName($product->getName());
                $productStatistics->setSku($product->getSku());
                $productStatistics->setTaxPaid(0);
                $productStatistics->setShippingPaid(0);
                $productStatistics->setFinancePaid(0);
                $productStatistics->setLessRefunded(0);
                $productStatistics->setTotalQuantity(0);
                $productStatistics->setRefundedQuantity(0);
                $productStatistics->setFreeQuantity(0);
                $productStatistics->setNetProduct(0);
                $productStatistics->setNetPaid(0);

                $result->addProductStatistics($productStatistics);
            }
        }

        $result->orderAccountingProductsBySku();

        // instead of using the database we'll just add up all the rows to calculate the totals
        // tax
        $result->setTaxPaid(0);
        foreach ($result->getAccountingProducts() as $accountingProduct) {
            $result->setTaxPaid($result->getTaxPaid() + $accountingProduct->getTaxPaid());
        }
        $result->setTaxPaid(round($result->getTaxPaid(), 2));

        // shipping
        $result->setShippingPaid(0);
        foreach ($result->getAccountingProducts() as $accountingProduct) {
            $result->setShippingPaid($result->getShippingPaid() + $accountingProduct->getShippingPaid());
        }
        $result->setShippingPaid(round($result->getShippingPaid(), 2));

        // finance
        $result->setFinancePaid(0);
        foreach ($result->getAccountingProducts() as $accountingProduct) {
            $result->setFinancePaid($result->getFinancePaid() + $accountingProduct->getFinancePaid());
        }
        $result->setShippingPaid(round($result->getShippingPaid(), 2));

        // refunded
        $result->setRefunded(0);
        foreach ($result->getAccountingProducts() as $accountingProduct) {
            $result->setRefunded($result->getRefunded() + $accountingProduct->getLessRefunded());
        }
        $result->setRefunded(round($result->getRefunded(), 2));

        // gross product
        $result->setNetProduct(0);
        foreach ($result->getAccountingProducts() as $accountingProduct) {
            $result->setNetProduct($result->getNetProduct() + $accountingProduct->getNetProduct());
        }
        $result->setNetProduct(round($result->getNetProduct(), 2));

        // net paid
        $result->setNetPaid(0);
        foreach ($result->getAccountingProducts() as $accountingProduct) {
            $result->setNetPaid($result->getNetPaid() + $accountingProduct->getNetPaid());
        }
        $result->setNetPaid(round($result->getNetPaid(), 2));

        return $result;
    }
}
