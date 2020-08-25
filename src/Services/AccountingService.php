<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\AccountingProduct;
use Railroad\Ecommerce\Entities\Structures\AccountingProductTotals;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\RefundRepository;
use Throwable;

/**
 * Class AccountingService
 * @package Railroad\Ecommerce\Services
 */
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
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * AccountingService constructor.
     *
     * @param PaymentRepository $paymentRepository
     * @param RefundRepository $refundRepository
     * @param ProductRepository $productRepository
     * @param DatabaseManager $databaseManager
     */
    public function __construct(
        PaymentRepository $paymentRepository,
        RefundRepository $refundRepository,
        ProductRepository $productRepository,
        DatabaseManager $databaseManager
    )
    {
        $this->paymentRepository = $paymentRepository;
        $this->refundRepository = $refundRepository;
        $this->productRepository = $productRepository;
        $this->databaseManager = $databaseManager;
    }

    public function indexByRequest(Request $request): AccountingProductTotals
    {
        ini_set('xdebug.var_display_max_depth', '10');
        ini_set('xdebug.var_display_max_children', '256');
        ini_set('xdebug.var_display_max_data', '1024');

        $connection = $this->databaseManager->connection(config('ecommerce.database_connection_name'));

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

        // fetch report summary totals, calculated at least partially in mysql
        $result = new AccountingProductTotals($smallDate, $bigDate);

        // get all the payments during this period to process
//        SELECT * FROM `ecommerce_payments` WHERE created_at BETWEEN '2020-05-01 08:00:00' AND '2020-06-01 08:00:00' AND gateway_name='drumeo' AND external_provider NOT IN ('apple', 'google') AND type='subscription_renewal' AND status='paid' AND external_id IS NOT NULL ORDER BY `ecommerce_payments`.`created_at` ASC

        $payments = $connection->table('ecommerce_payments')
            ->select(
                [
                    'ecommerce_payments.id as payment_id',
                    'ecommerce_payments.total_paid as payment_total_paid',
                    'ecommerce_payments.type as payment_type',
                    'ecommerce_payments.external_provider as payment_external_provider',

                    'ecommerce_payment_taxes.product_taxes_paid as payment_product_taxes_paid',
                    'ecommerce_payment_taxes.shipping_taxes_paid as payment_shipping_taxes_paid',

                    'ecommerce_orders.id as order_id',
                    'ecommerce_orders.total_due as order_total_due',
                    'ecommerce_orders.product_due as order_product_due',
                    'ecommerce_orders.taxes_due as order_taxes_due',
                    'ecommerce_orders.shipping_due as order_shipping_due',
                    'ecommerce_orders.finance_due as order_finance_due',
                    'ecommerce_orders.total_paid as order_total_paid',

                    'ecommerce_order_items.id as order_item_id',
                    'ecommerce_order_items.final_price as order_item_final_price',
                    'ecommerce_order_items.quantity as order_item_quantity',
                    'ecommerce_order_item_products.sku as order_item_product_sku',
                    'ecommerce_order_item_products.name as order_item_product_name',
                    'ecommerce_order_item_products.type as order_item_product_type',
                    'ecommerce_order_item_products.weight as order_item_product_weight',

                    'ecommerce_subscriptions.id as subscription_id',
                    'ecommerce_subscriptions.type as subscription_type',
                    'ecommerce_subscriptions.total_price as subscription_total_price',
                    'ecommerce_subscriptions.user_id as subscription_user_id',
                    'ecommerce_products.sku as subscription_product_sku',
                    'ecommerce_products.name as subscription_product_name',

                    'ecommerce_subscription_orders.total_due as subscription_order_total_due',
                    'ecommerce_subscription_orders.product_due as subscription_order_product_due',
                    'ecommerce_subscription_orders.taxes_due as subscription_order_taxes_due',
                    'ecommerce_subscription_orders.shipping_due as subscription_order_shipping_due',
                    'ecommerce_subscription_orders.finance_due as subscription_order_finance_due',
                    'ecommerce_subscription_orders.total_paid as subscription_order_total_paid',

                    'ecommerce_subscription_order_items.id as subscription_order_item_id',
                    'ecommerce_subscription_order_items.final_price as subscription_order_item_final_price',
                    'ecommerce_subscription_order_items.quantity as subscription_order_item_quantity',
                    'ecommerce_subscription_order_item_products.sku as subscription_order_item_product_sku',
                    'ecommerce_subscription_order_item_products.name as subscription_order_item_product_name',
                    'ecommerce_subscription_order_item_products.type as subscription_order_item_product_type',
                    'ecommerce_subscription_order_item_products.weight as subscription_order_item_product_weight',
                ]
            )
            ->leftJoin('ecommerce_payment_taxes', 'ecommerce_payment_taxes.payment_id', '=', 'ecommerce_payments.id')
            ->leftJoin('ecommerce_order_payments', 'ecommerce_order_payments.payment_id', '=', 'ecommerce_payments.id')
            ->leftJoin('ecommerce_orders', 'ecommerce_orders.id', '=', 'ecommerce_order_payments.order_id')
            ->leftJoin(
                'ecommerce_order_items',
                'ecommerce_order_items.order_id',
                '=',
                'ecommerce_orders.id'
            ) // this adds more duplicate rows, must be reduced properly
            ->leftJoin(
                'ecommerce_products as ecommerce_order_item_products',
                'ecommerce_order_item_products.id',
                '=',
                'ecommerce_order_items.product_id'
            )
            ->leftJoin(
                'ecommerce_subscription_payments',
                'ecommerce_subscription_payments.payment_id',
                '=',
                'ecommerce_payments.id'
            )
            ->leftJoin(
                'ecommerce_subscriptions',
                'ecommerce_subscriptions.id',
                '=',
                'ecommerce_subscription_payments.subscription_id'
            )
            ->leftJoin('ecommerce_products', 'ecommerce_products.id', '=', 'ecommerce_subscriptions.product_id')
            ->leftJoin('ecommerce_orders as ecommerce_subscription_orders', 'ecommerce_subscription_orders.id', '=', 'ecommerce_subscriptions.order_id')
            ->leftJoin(
                'ecommerce_order_items as ecommerce_subscription_order_items',
                'ecommerce_subscription_order_items.order_id',
                '=',
                'ecommerce_subscriptions.order_id'
            ) // this adds more duplicate rows, must be reduced properly
            ->leftJoin(
                'ecommerce_products as ecommerce_subscription_order_item_products',
                'ecommerce_subscription_order_item_products.id',
                '=',
                'ecommerce_subscription_order_items.product_id'
            )
            ->whereBetween('ecommerce_payments.created_at', [$smallDateTime, $bigDateTime])
            ->where('ecommerce_payments.gateway_name', $brand)
            ->where('ecommerce_payments.status', '!=', Payment::STATUS_FAILED)
            ->get();

        // now lets group the data structure so we have 1 top level array element per payment and no duplicates
        $paymentsArrayGrouped = [];

        $totalPaid = 0;

        foreach ($payments as $payment) {
            $paymentsArrayGrouped[$payment->payment_id]['payment_id'] = $payment->payment_id;
            $paymentsArrayGrouped[$payment->payment_id]['payment_total_paid'] = $payment->payment_total_paid;
            $paymentsArrayGrouped[$payment->payment_id]['payment_type'] = $payment->payment_type;
            $paymentsArrayGrouped[$payment->payment_id]['payment_external_provider'] = $payment->payment_external_provider;

            $paymentsArrayGrouped[$payment->payment_id]['payment_product_taxes_paid'] = $payment->payment_product_taxes_paid;
            $paymentsArrayGrouped[$payment->payment_id]['payment_shipping_taxes_paid'] = $payment->payment_shipping_taxes_paid;

            if (!empty($payment->order_id)) {
                $paymentsArrayGrouped[$payment->payment_id]['order']['order_id'] = $payment->order_id;
                $paymentsArrayGrouped[$payment->payment_id]['order']['order_total_due'] = $payment->order_total_due;
                $paymentsArrayGrouped[$payment->payment_id]['order']['order_product_due'] = $payment->order_product_due;
                $paymentsArrayGrouped[$payment->payment_id]['order']['order_taxes_due'] = $payment->order_taxes_due;
                $paymentsArrayGrouped[$payment->payment_id]['order']['order_shipping_due'] = $payment->order_shipping_due;
                $paymentsArrayGrouped[$payment->payment_id]['order']['order_finance_due'] = $payment->order_finance_due;
                $paymentsArrayGrouped[$payment->payment_id]['order']['order_total_paid'] = $payment->order_total_paid;

                $paymentsArrayGrouped[$payment->payment_id]['order']['items'][$payment->order_item_id]['order_item_id'] = $payment->order_item_id;
                $paymentsArrayGrouped[$payment->payment_id]['order']['items'][$payment->order_item_id]['order_item_final_price'] = $payment->order_item_final_price;
                $paymentsArrayGrouped[$payment->payment_id]['order']['items'][$payment->order_item_id]['order_item_quantity'] = $payment->order_item_quantity;
                $paymentsArrayGrouped[$payment->payment_id]['order']['items'][$payment->order_item_id]['order_item_product_sku'] = $payment->order_item_product_sku;
                $paymentsArrayGrouped[$payment->payment_id]['order']['items'][$payment->order_item_id]['order_item_product_name'] = $payment->order_item_product_name;
                $paymentsArrayGrouped[$payment->payment_id]['order']['items'][$payment->order_item_id]['order_item_product_type'] = $payment->order_item_product_type;
                $paymentsArrayGrouped[$payment->payment_id]['order']['items'][$payment->order_item_id]['order_item_product_weight'] = $payment->order_item_product_weight;
            }

            if (!empty($payment->subscription_id)) {
                $paymentsArrayGrouped[$payment->payment_id]['subscription']['subscription_id'] = $payment->subscription_id;
                $paymentsArrayGrouped[$payment->payment_id]['subscription']['subscription_type'] = $payment->subscription_type;
                $paymentsArrayGrouped[$payment->payment_id]['subscription']['subscription_total_price'] = $payment->subscription_total_price;
                $paymentsArrayGrouped[$payment->payment_id]['subscription']['subscription_user_id'] = $payment->subscription_user_id;
                $paymentsArrayGrouped[$payment->payment_id]['subscription']['subscription_product_sku'] = $payment->subscription_product_sku;
                $paymentsArrayGrouped[$payment->payment_id]['subscription']['subscription_product_name'] = $payment->subscription_product_name;

                $paymentsArrayGrouped[$payment->payment_id]['subscription']['order']['subscription_order_total_due'] = $payment->subscription_order_total_due;
                $paymentsArrayGrouped[$payment->payment_id]['subscription']['order']['subscription_order_product_due'] = $payment->subscription_order_product_due;
                $paymentsArrayGrouped[$payment->payment_id]['subscription']['order']['subscription_order_taxes_due'] = $payment->subscription_order_taxes_due;
                $paymentsArrayGrouped[$payment->payment_id]['subscription']['order']['subscription_order_shipping_due'] = $payment->subscription_order_shipping_due;
                $paymentsArrayGrouped[$payment->payment_id]['subscription']['order']['subscription_order_finance_due'] = $payment->subscription_order_finance_due;
                $paymentsArrayGrouped[$payment->payment_id]['subscription']['order']['subscription_order_total_paid'] = $payment->subscription_order_total_paid;

                $paymentsArrayGrouped[$payment->payment_id]['subscription']['order']['items'][$payment->subscription_order_item_id]['subscription_order_item_id'] = $payment->subscription_order_item_id;
                $paymentsArrayGrouped[$payment->payment_id]['subscription']['order']['items'][$payment->subscription_order_item_id]['subscription_order_item_final_price'] = $payment->subscription_order_item_final_price;
                $paymentsArrayGrouped[$payment->payment_id]['subscription']['order']['items'][$payment->subscription_order_item_id]['subscription_order_item_quantity'] = $payment->subscription_order_item_quantity;
                $paymentsArrayGrouped[$payment->payment_id]['subscription']['order']['items'][$payment->subscription_order_item_id]['subscription_order_item_product_sku'] = $payment->subscription_order_item_product_sku;
                $paymentsArrayGrouped[$payment->payment_id]['subscription']['order']['items'][$payment->subscription_order_item_id]['subscription_order_item_product_name'] = $payment->subscription_order_item_product_name;
                $paymentsArrayGrouped[$payment->payment_id]['subscription']['order']['items'][$payment->subscription_order_item_id]['subscription_order_item_product_type'] = $payment->subscription_order_item_product_type;
                $paymentsArrayGrouped[$payment->payment_id]['subscription']['order']['items'][$payment->subscription_order_item_id]['subscription_order_item_product_weight'] = $payment->subscription_order_item_product_weight;
            }
        }

        foreach ($paymentsArrayGrouped as $paymentArrayGrouped) {
            $totalPaid += $paymentArrayGrouped['payment_total_paid'];
        }

        var_dump($totalPaid);
        var_dump(array_slice($paymentsArrayGrouped, 0, 100));
        dd(1);
    }

    /**
     * @param Request $request
     *
     * @return AccountingProductTotals
     *
     * @throws Throwable
     */
    public function _indexByRequest(Request $request): AccountingProductTotals
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

        // fetch report summary totals, calculated at least partially in mysql
        $result = new AccountingProductTotals($smallDate, $bigDate);

        // -----------------------------------------------
        // INITIAL ORDERS

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

            // sometimes the productDue is way higher than the sum of the order item prices, so instead we'll just add
            // them up to make the order item ratio
            $sumOfOrderItemPrices = 0;

            foreach ($orderData['items'] as $productId => $productData) {
                $sumOfOrderItemPrices += $productData['finalPrice'];
            }

            // if its a payment plan we must also multiply by the paid ratio
            // must exclude shipping since its always charged on the first payment
            if ($orderData['totalDue'] != $orderData['totalPaid'] &&
                $orderData['totalDue'] > 0
            ) {
                $paidRatio =
                    ($orderData['totalPaid'] - $orderData['shippingDue']) /
                    ($orderData['totalDue'] - $orderData['shippingDue']);
            } else {
                $paidRatio = 1;
            }

            foreach ($orderData['items'] as $productId => $productData) {

                $orderItemRatio = 0;

                if (
                    $sumOfOrderItemPrices != 0
                    && $sumOfOrderItemPrices != null
                    && $productData['finalPrice']
                ) {
                    $orderItemRatio = $productData['finalPrice'] / $sumOfOrderItemPrices;
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

                $finance = 0;

                $tax = $orderItemRatio * $orderData['taxesDue'] * $paidRatio;
                $productsMap[$productId]['taxPaid'] += $tax;

                if ((float)$orderData['shippingDue'] > 0 &&
                    $productData['productType'] == Product::TYPE_PHYSICAL_ONE_TIME) {
                    if ((float)$productData['finalPrice'] > 0) {
                        $paidPhysical[] = $productId;
                    } else {
                        $freePhysical[] = $productId;
                    }
                }

                if ((float)$orderData['financeDue'] > 0) {
                    $finance = $orderItemRatio * $orderData['financeDue'] * $paidRatio;
                    $productsMap[$productId]['financePaid'] += $finance;
                }

                $productsMap[$productId]['netProduct'] += $orderData['productDue'] * $paidRatio * $orderItemRatio;
                $productsMap[$productId]['netPaid'] += ($orderData['productDue'] * $paidRatio * $orderItemRatio) +
                    $tax +
                    $finance;
                $productsMap[$productId]['quantity'] += $productData['quantity'];

                if ($productData['finalPrice'] == 0) {
                    $productsMap[$productId]['freeQuantity'] += $productData['quantity'];
                }
            }
        }

        $ordersProductsData = null;

        // -----------------------------------------------
        // PAYMENT PLAN PAYMENTS (after the first initial payment)

        // fetch payment plans data
        $paymentPlansProductsData =
            $this->paymentRepository->getPaymentPlansProductsData($smallDateTime, $bigDateTime, $brand);

        $ordersMap = [];

        // restructure mysql result data into groups of order items
        foreach ($paymentPlansProductsData as $paymentPlanProductsData) {
            if (!isset($ordersMap[$paymentPlanProductsData['orderId']])) {
                $ordersMap[$paymentPlanProductsData['orderId']] = [
                    'totalDue' => $paymentPlanProductsData['totalDue'],
                    'productDue' => 0,
                    'taxesDue' => $paymentPlanProductsData['taxesDue'],
                    'shippingDue' => $paymentPlanProductsData['shippingDue'],
                    'financeDue' => $paymentPlanProductsData['financeDue'],
                    'totalPaid' => $paymentPlanProductsData['totalPaid'],
                    'items' => [],
                ];
            }

            // some orders have NULL productDue in mysql, thus order items sum is recalculated
            $ordersMap[$paymentPlanProductsData['orderId']]['productDue'] += $paymentPlanProductsData['finalPrice'];

            $ordersMap[$paymentPlanProductsData['orderId']]['items'][$paymentPlanProductsData['productId']] = [
                'quantity' => $paymentPlanProductsData['quantity'],
                'finalPrice' => $paymentPlanProductsData['finalPrice'],
                'productSku' => $paymentPlanProductsData['productSku'],
                'productName' => $paymentPlanProductsData['productName'],
                'productType' => $orderProductData['productType'],
            ];
        }

        // for each group of order items, start calculating product stats
        foreach ($ordersMap as $orderId => $orderData) {

            // sometimes the productDue is way higher than the sum of the order item prices, so instead we'll just add
            // them up to make the order item ratio
            $sumOfOrderItemPrices = 0;

            foreach ($orderData['items'] as $productId => $productData) {
                $sumOfOrderItemPrices += $productData['finalPrice'];
            }

            // if its a payment plan we must also multiply by the paid ratio
            // must exclude shipping since its always charged on the first payment
            if ($orderData['totalDue'] != $orderData['totalPaid'] &&
                $orderData['totalDue'] > 0
            ) {
                $paidRatio =
                    ($orderData['totalPaid'] - $orderData['shippingDue']) /
                    ($orderData['totalDue'] - $orderData['shippingDue']);
            } else {
                $paidRatio = 1;
            }

            foreach ($orderData['items'] as $productId => $productData) {

                $orderItemRatio = 0;

                if (
                    $sumOfOrderItemPrices != 0
                    && $sumOfOrderItemPrices != null
                    && $productData['finalPrice']
                ) {
                    $orderItemRatio = $productData['finalPrice'] / $sumOfOrderItemPrices;
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

                $finance = 0;

                $tax = $orderItemRatio * $orderData['taxesDue'] * $paidRatio;
                $productsMap[$productId]['taxPaid'] += $tax;

                if ((float)$orderData['financeDue'] > 0) {
                    $finance = $orderItemRatio * $orderData['financeDue'] * $paidRatio;
                    $productsMap[$productId]['financePaid'] += $finance;
                }

                $productsMap[$productId]['netProduct'] += $orderData['productDue'] * $paidRatio * $orderItemRatio;
                $productsMap[$productId]['netPaid'] += ($orderData['productDue'] * $paidRatio * $orderItemRatio) +
                    $tax +
                    $finance;
                $productsMap[$productId]['quantity'] += $productData['quantity'];
            }
        }

        $paymentPlansProductsData = null;

        // -----------------------------------------------
        // SUBSCRIPTION RENEWAL PAYMENTS

        // fetch renewed subscriptions data
        $subscriptionsProductsData =
            $this->paymentRepository->getSubscriptionsProductsData($smallDateTime, $bigDateTime, $brand);

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
            $productsMap[$productId]['netProduct'] += $subscriptionProductData['totalPrice'] -
                $subscriptionProductData['tax'];
            $productsMap[$productId]['netPaid'] += $subscriptionProductData['totalPrice'];
            $productsMap[$productId]['quantity'] += 1;
        }

        $subscriptionsProductsData = null;

        // -----------------------------------------------
        // ORDER PAYMENT REFUNDS (after the first initial payment)
        $refundOrdersProductsData =
            $this->refundRepository->getAccountingOrderProductsData($smallDateTime, $bigDateTime, $brand);

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

                $orderItemRatio = 0;

                if ($refundOrderData['hasPaidItems']) {
                    if (
                        $refundOrderData['productDue'] != 0
                        && $refundOrderData['productDue'] != null
                        && $productData['finalPrice']
                    ) {
                        $orderItemRatio = $productData['finalPrice'] / $refundOrderData['productDue'];
                    }
                } else {
                    $orderItemsCount = count($refundOrderData['items']) > 0 ? count($refundOrderData['items']) : 1;
                    $orderItemRatio = 1 / $orderItemsCount;
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
                    $refundedForProduct = $orderItemRatio * $refundedAmount;
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

        $refundOrdersProductsData = null;

        // -----------------------------------------------
        // SUBSCRIPTION RENEWALS REFUNDS (after the first initial payment)

        $refundSubscriptionsProductsData =
            $this->refundRepository->getAccountingSubscriptionProductsData($smallDateTime, $bigDateTime, $brand);

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

        $refundPaymentPlansProductsData =
            $this->refundRepository->getAccountingPaymentPlansProductsData($smallDateTime, $bigDateTime, $brand);

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

                $orderItemRatio = 0;

                if ($refundOrderData['hasPaidItems']) {
                    if (
                        $refundOrderData['productDue'] != 0
                        && $refundOrderData['productDue'] != null
                        && $productData['finalPrice']
                    ) {
                        $orderItemRatio = $productData['finalPrice'] / $refundOrderData['productDue'];
                    }
                } else {
                    $orderItemsCount = count($refundOrderData['items']) > 0 ? count($refundOrderData['items']) : 1;
                    $orderItemRatio = 1 / $orderItemsCount;
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
                    $refundedForProduct = $orderItemRatio * $refundedAmount;
                    $productsMap[$productId]['refunded'] += $refundedForProduct;
                    $productsMap[$productId]['netPaid'] -= $refundedForProduct;
                }

                // quantity data for payment plans refunds is considered only on initial order payment
            }
        }

        $refundPaymentPlansProductsData = null;

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
