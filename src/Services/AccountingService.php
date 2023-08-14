<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\AccountingProduct;
use Railroad\Ecommerce\Entities\Structures\AccountingProductTotals;
use Railroad\Ecommerce\Entities\Subscription;
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
    ) {
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

        $brands = [$brand];
//        if ($brand == 'drumeo') {
//            //add musora gateway to drumeo report
//            $brands[] = 'musora';
//        }

        // get all the payments and refunds during this period to process
        $payments = $connection->table('ecommerce_payments')
            ->select(
                [
                    'ecommerce_payments.id as payment_id',
                    'ecommerce_payments.total_paid as payment_total_paid',
                    'ecommerce_payments.type as payment_type',
                    'ecommerce_payments.external_provider as payment_external_provider',
                    'ecommerce_payments.created_at as payment_created_at',

                    'ecommerce_payment_taxes.product_taxes_paid as payment_product_taxes_paid',
                    'ecommerce_payment_taxes.shipping_taxes_paid as payment_shipping_taxes_paid',
                ]
            )
            ->leftJoin('ecommerce_payment_taxes', 'ecommerce_payment_taxes.payment_id', '=', 'ecommerce_payments.id')
            ->leftJoin('ecommerce_order_payments', 'ecommerce_order_payments.payment_id', '=', 'ecommerce_payments.id')
            ->leftJoin('ecommerce_orders', 'ecommerce_orders.id', '=', 'ecommerce_order_payments.order_id')
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
            ->whereBetween('ecommerce_payments.created_at', [$smallDateTime, $bigDateTime])
            ->where(function($builder) use ($brands) {
                return $builder->whereIn('ecommerce_subscriptions.brand', $brands)
                    ->orWhereIn('ecommerce_orders.brand', $brands);
            })
//            ->whereIn('ecommerce_payments.gateway_name', $brands)
            ->where('ecommerce_payments.status', '!=', Payment::STATUS_FAILED)
//            ->where('ecommerce_payments.id', 347634) // testing only
            ->get()
            ->toArray();

        $orderPayments = $connection->table('ecommerce_payments')
            ->select(
                [
                    'ecommerce_payments.id as payment_id',
                    'ecommerce_payments.total_paid as payment_total_paid',
                    'ecommerce_payments.type as payment_type',
                    'ecommerce_payments.external_provider as payment_external_provider',
                    'ecommerce_payments.created_at as payment_created_at',

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
                    'ecommerce_order_item_products.id as order_item_product_id',
                    'ecommerce_order_item_products.sku as order_item_product_sku',
                    'ecommerce_order_item_products.name as order_item_product_name',
                    'ecommerce_order_item_products.type as order_item_product_type',
                    'ecommerce_order_item_products.weight as order_item_product_weight',
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
            ->whereBetween('ecommerce_payments.created_at', [$smallDateTime, $bigDateTime])
            ->whereIn('ecommerce_orders.brand', $brands)
            ->where('ecommerce_payments.status', '!=', Payment::STATUS_FAILED)
//            ->where('ecommerce_payments.id', 347634) // testing only
            ->get()
            ->toArray();

        $subPayments = $connection->table('ecommerce_payments')
            ->select(
                [
                    'ecommerce_payments.id as payment_id',
                    'ecommerce_payments.total_paid as payment_total_paid',
                    'ecommerce_payments.type as payment_type',
                    'ecommerce_payments.external_provider as payment_external_provider',
                    'ecommerce_payments.created_at as payment_created_at',

                    'ecommerce_payment_taxes.product_taxes_paid as payment_product_taxes_paid',
                    'ecommerce_payment_taxes.shipping_taxes_paid as payment_shipping_taxes_paid',

                    'ecommerce_subscriptions.id as subscription_id',
                    'ecommerce_subscriptions.type as subscription_type',
                    'ecommerce_subscriptions.total_price as subscription_total_price',
                    'ecommerce_subscriptions.user_id as subscription_user_id',
                    'ecommerce_products.sku as subscription_product_sku',
                    'ecommerce_products.id as subscription_product_id',
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
                    'ecommerce_subscription_order_item_products.id as subscription_order_item_product_id',
                    'ecommerce_subscription_order_item_products.sku as subscription_order_item_product_sku',
                    'ecommerce_subscription_order_item_products.name as subscription_order_item_product_name',
                    'ecommerce_subscription_order_item_products.type as subscription_order_item_product_type',
                    'ecommerce_subscription_order_item_products.weight as subscription_order_item_product_weight',
                ]
            )
            ->leftJoin('ecommerce_payment_taxes', 'ecommerce_payment_taxes.payment_id', '=', 'ecommerce_payments.id')
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
            ->leftJoin(
                'ecommerce_orders as ecommerce_subscription_orders',
                'ecommerce_subscription_orders.id',
                '=',
                'ecommerce_subscriptions.order_id'
            )
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
            ->whereIn('ecommerce_subscriptions.brand', $brands)
            ->where('ecommerce_payments.status', '!=', Payment::STATUS_FAILED)
//            ->where('ecommerce_payments.id', 347634) // testing only
            ->get()
            ->toArray();

        $payments = array_merge($payments, $orderPayments, $subPayments);

        $refunds = $connection->table('ecommerce_refunds')
            ->select(
                [
                    'ecommerce_refunds.id as refund_id',
                    'ecommerce_refunds.refunded_amount as refund_amount',

                    'ecommerce_payments.id as payment_id',
                    'ecommerce_payments.total_paid as payment_total_paid',
                    'ecommerce_payments.type as payment_type',
                    'ecommerce_payments.external_provider as payment_external_provider',
                    'ecommerce_payments.created_at as payment_created_at',

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
                    'ecommerce_order_item_products.id as order_item_product_id',
                    'ecommerce_order_item_products.sku as order_item_product_sku',
                    'ecommerce_order_item_products.name as order_item_product_name',
                    'ecommerce_order_item_products.type as order_item_product_type',
                    'ecommerce_order_item_products.weight as order_item_product_weight',

                    'ecommerce_subscriptions.id as subscription_id',
                    'ecommerce_subscriptions.type as subscription_type',
                    'ecommerce_subscriptions.total_price as subscription_total_price',
                    'ecommerce_subscriptions.user_id as subscription_user_id',
                    'ecommerce_products.sku as subscription_product_sku',
                    'ecommerce_products.id as subscription_product_id',
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
                    'ecommerce_subscription_order_item_products.id as subscription_order_item_product_id',
                    'ecommerce_subscription_order_item_products.sku as subscription_order_item_product_sku',
                    'ecommerce_subscription_order_item_products.name as subscription_order_item_product_name',
                    'ecommerce_subscription_order_item_products.type as subscription_order_item_product_type',
                    'ecommerce_subscription_order_item_products.weight as subscription_order_item_product_weight',
                ]
            )
            ->leftJoin('ecommerce_payments', 'ecommerce_payments.id', '=', 'ecommerce_refunds.payment_id')
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
            ->leftJoin(
                'ecommerce_orders as ecommerce_subscription_orders',
                'ecommerce_subscription_orders.id',
                '=',
                'ecommerce_subscriptions.order_id'
            )
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
            ->whereBetween('ecommerce_refunds.created_at', [$smallDateTime, $bigDateTime])
            ->where(function($builder) use ($brands) {
                return $builder->whereIn('ecommerce_subscriptions.brand', $brands)
                    ->orWhereIn('ecommerce_orders.brand', $brands);
            })
            ->where('ecommerce_payments.status', '!=', Payment::STATUS_FAILED)
//            ->where('ecommerce_payments.id', 352096) // testing only
            ->get();

        // now lets group the data structure so we have 1 top level array element per payment and no duplicates
        $paymentsArrayGrouped = [];

        foreach (array_merge($payments, $refunds->toArray()) as $payment) {
            // if its a refund we should ignore the payment itself in the main calculations
            $payment->refund_id = $payment->refund_id ?? null;

            $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['refund_amount'] =
                $payment->refund_amount ?? 0;
            $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['refund_id'] =
                $payment->refund_id ?? null;

            $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['payment_id'] =
                $payment->payment_id;
            $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['payment_created_at'] =
                $payment->payment_created_at;
            $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['payment_total_paid'] =
                (float)$payment->payment_total_paid;
            $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['payment_type'] =
                $payment->payment_type;
            $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['payment_external_provider'] =
                $payment->payment_external_provider;

            $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['payment_product_taxes_paid'] =
                (float)$payment->payment_product_taxes_paid;
            $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['payment_shipping_taxes_paid'] =
                (float)$payment->payment_shipping_taxes_paid;

            if (!empty($payment->order_id)) {
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['order']['order_id'] =
                    $payment->order_id;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['order']['order_total_due'] =
                    (float)$payment->order_total_due;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['order']['order_product_due'] =
                    (float)$payment->order_product_due;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['order']['order_taxes_due'] =
                    (float)$payment->order_taxes_due;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['order']['order_shipping_due'] =
                    (float)$payment->order_shipping_due;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['order']['order_finance_due'] =
                    (float)$payment->order_finance_due;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['order']['order_total_paid'] =
                    (float)$payment->order_total_paid;

                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['order']['items'][$payment->order_item_id]['order_item_id'] =
                    $payment->order_item_id;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['order']['items'][$payment->order_item_id]['order_item_final_price'] =
                    (float)$payment->order_item_final_price;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['order']['items'][$payment->order_item_id]['order_item_quantity'] =
                    $payment->order_item_quantity;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['order']['items'][$payment->order_item_id]['order_item_product_sku'] =
                    $payment->order_item_product_sku;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['order']['items'][$payment->order_item_id]['order_item_product_id'] =
                    $payment->order_item_product_id;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['order']['items'][$payment->order_item_id]['order_item_product_name'] =
                    $payment->order_item_product_name;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['order']['items'][$payment->order_item_id]['order_item_product_type'] =
                    $payment->order_item_product_type;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['order']['items'][$payment->order_item_id]['order_item_product_weight'] =
                    (float)$payment->order_item_product_weight;
            }

            if (!empty($payment->subscription_id)) {
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['subscription']['subscription_id'] =
                    $payment->subscription_id;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['subscription']['subscription_type'] =
                    $payment->subscription_type;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['subscription']['subscription_total_price'] =
                    (float)$payment->subscription_total_price;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['subscription']['subscription_user_id'] =
                    $payment->subscription_user_id;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['subscription']['subscription_product_sku'] =
                    $payment->subscription_product_sku;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['subscription']['subscription_product_id'] =
                    $payment->subscription_product_id;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['subscription']['subscription_product_name'] =
                    $payment->subscription_product_name;

                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['subscription']['order']['order_total_due'] =
                    (float)$payment->subscription_order_total_due;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['subscription']['order']['order_product_due'] =
                    (float)$payment->subscription_order_product_due;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['subscription']['order']['order_taxes_due'] =
                    (float)$payment->subscription_order_taxes_due;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['subscription']['order']['order_shipping_due'] =
                    (float)$payment->subscription_order_shipping_due;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['subscription']['order']['order_finance_due'] =
                    (float)$payment->subscription_order_finance_due;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['subscription']['order']['order_total_paid'] =
                    (float)$payment->subscription_order_total_paid;

                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['subscription']['order']['items'][$payment->subscription_order_item_id]['order_item_id'] =
                    $payment->subscription_order_item_id;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['subscription']['order']['items'][$payment->subscription_order_item_id]['order_item_final_price'] =
                    (float)$payment->subscription_order_item_final_price;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['subscription']['order']['items'][$payment->subscription_order_item_id]['order_item_quantity'] =
                    $payment->subscription_order_item_quantity;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['subscription']['order']['items'][$payment->subscription_order_item_id]['order_item_product_id'] =
                    $payment->subscription_order_item_product_id;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['subscription']['order']['items'][$payment->subscription_order_item_id]['order_item_product_sku'] =
                    $payment->subscription_order_item_product_sku;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['subscription']['order']['items'][$payment->subscription_order_item_id]['order_item_product_name'] =
                    $payment->subscription_order_item_product_name;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['subscription']['order']['items'][$payment->subscription_order_item_id]['order_item_product_type'] =
                    $payment->subscription_order_item_product_type;
                $paymentsArrayGrouped[$payment->payment_id . $payment->refund_id]['subscription']['order']['items'][$payment->subscription_order_item_id]['order_item_product_weight'] =
                    (float)$payment->subscription_order_item_product_weight;
            }
        }

        // for internal tracking and testing
        $totalPaidDatabase = 0;
        $totalRefundedDatabase = 0;
        $paymentsGrandTotalPaidTracked = 0;

        foreach ($paymentsArrayGrouped as $paymentArrayGrouped) {
            if ($paymentArrayGrouped['refund_amount'] == 0) {
                $totalPaidDatabase += $paymentArrayGrouped['payment_total_paid'];
            } else {
                $totalRefundedDatabase += $paymentArrayGrouped['refund_amount'];
            }
        }

        // testing code
//        $handledPaymentIds = [];
//        $handledRefundIds = [];
//
//        var_dump('Total payments to process: ' . $payments->pluck('payment_id')->unique()->count());
//        var_dump('Total refunds to process: ' . $refunds->pluck('payment_id')->unique()->count());

        // the final data array we are after
        $productMapElementExample = [
            'taxPaid' => 0,
            'shippingPaid' => 0,
            'financePaid' => 0,
            'recurringProductPaid' => 0,
            // only for subscription product renewals, and only including payments after the first
            'productPaid' => 0,
            'grossPaid' => 0,
            // this is the grand total paid for the product itself (excluding tax, shipping finance)
            'netPaid' => 0,
            // this is the net paid including tax, shipping, finance, and with subtracted refunds
            'productSku' => 'sku',
            'productName' => 'name',
            'refunded' => 0,
            'quantity' => 0,
            'refundedQuantity' => 0,
            'freeQuantity' => 0,
        ];

        $productsMap = [];

        // here is a list of the ratios we need per payment to figure out the map values
        // total paid for this payment / total due for order = paid ratio
        // order item cost / total of all order item costs = order item cost ratio
        // order item weight / total of all order item weights = order item weight ratio
        // refund amount / original payment amount = refund ratio

        foreach ($paymentsArrayGrouped as $paymentId => $paymentData) {
            // figure out if we should associate this payment with only the subscription (membership renewals)
            // or if it should be associated with an order or a payment plan renewal for an order

            // if its a payment plan payment or there is no subscription or the payment is the initial payment,
            // use the original order
            if ((!empty($paymentData['subscription']) &&
                    $paymentData['subscription']['subscription_type'] == Subscription::TYPE_PAYMENT_PLAN) ||
                empty($paymentData['subscription']) || $paymentData['payment_type'] == Payment::TYPE_INITIAL_ORDER
            ) {
                // use initial order linked to payment
                if (!empty($paymentData['order']) && !empty($paymentData['order']['items'])) {
                    $orderToUse = $paymentData['order'];
                    // use the order attached to the subscription
                } elseif (!empty($paymentData['subscription']['order']) &&
                    !empty($paymentData['subscription']['order']['items'])) {
                    $orderToUse = $paymentData['subscription']['order'];
                } else {
                    // this means something is wrong with the data, we'll skip for now
                    continue;
                }

                // calculate the paid ratio
                if ($orderToUse['order_total_due'] > 0) {
                    $paidRatio = $paymentData['payment_total_paid'] / $orderToUse['order_total_due'];
                } else {
                    $paidRatio = 1;
                }

                // calculate order item cost ratio
                $totalCostOfOrderItems = 0;

                foreach ($orderToUse['items'] as $paymentOrderItem) {
                    $totalCostOfOrderItems += $paymentOrderItem['order_item_final_price'];
                }

                // calculate order item weight ratio
                $totalWeightOfOrderItems = 0;

                foreach ($orderToUse['items'] as $paymentOrderItem) {
                    $totalWeightOfOrderItems += $paymentOrderItem['order_item_product_weight'];
                }

                // calculate refund ratio if applicable
                if ($paymentData['payment_total_paid'] > 0 && $paymentData['refund_amount'] > 0) {
                    $refundRatio = $paymentData['refund_amount'] / $paymentData['payment_total_paid'];
                } else {
                    $refundRatio = 0;
                }

                // add the product map values
                $thisOrderNet = 0;

                foreach ($orderToUse['items'] as $paymentOrderItem) {
                    // calculate order item cost ratio for this item
                    if ($totalCostOfOrderItems > 0) {
                        $orderItemCostRatio = $paymentOrderItem['order_item_final_price'] / $totalCostOfOrderItems;
                    } else {
                        $orderItemCostRatio = 1;
                    }

                    // calculate order item weight ratio for this item
                    if ($totalWeightOfOrderItems > 0) {
                        $orderItemWeightRatio =
                            $paymentOrderItem['order_item_product_weight'] / $totalWeightOfOrderItems;
                    } else {
                        $orderItemWeightRatio = 1;
                    }

                    // create the first map array element for this product if its not already created
                    $productMap = $productsMap[$paymentOrderItem['order_item_product_id']] ?? $productMapElementExample;

                    if (empty($paymentOrderItem['order_item_product_id'])) {
                        // this means there is something wrong with the data, skip for now
                        continue 2;
                    }

                    // set tax, shipping, and finance
                    $taxPaid = $orderToUse['order_taxes_due'] * $paidRatio * $orderItemCostRatio;
                    $financePaid = $orderToUse['order_finance_due'] * $paidRatio * $orderItemCostRatio;
                    $shippingPaid = 0;

                    // if its the first payment, associate all shipping with it since shipping is always paid up front
                    if ($paymentData['payment_type'] == Payment::TYPE_INITIAL_ORDER) {
                        $shippingPaid = $orderToUse['order_shipping_due'] * $orderItemWeightRatio;
                    }

                    // set net product due, which is the cost for the product only, no tax, shipping, or finance
                    $productPaid =
                        ($paymentData['payment_total_paid'] * $orderItemCostRatio) -
                        $taxPaid -
                        $financePaid -
                        $shippingPaid;

                    $quantity = $paymentOrderItem['order_item_quantity'];

                    // ---------------------------
                    // set the product map

                    // if its a refund we should subtract all the totals instead of adding
                    if (!empty($paymentData['refund_amount']) && $paymentData['refund_amount'] > 0) {
                        $productPaid = -$productPaid * $refundRatio;
                        $taxPaid = -$taxPaid * $refundRatio;
                        $shippingPaid = -$shippingPaid * $refundRatio;
                        $financePaid = -$financePaid * $refundRatio;
                        $quantity = -$quantity * $refundRatio;
                    }

                    $productMap['productPaid'] += $productPaid;

                    $productMap['grossPaid'] += $productPaid;
                    $productMap['netPaid'] +=
                        $productPaid + $taxPaid + $shippingPaid + $financePaid;

                    $thisOrderNet += $productPaid + $taxPaid + $shippingPaid + $financePaid;

                    // set product info and rest
                    $productMap['productSku'] = $paymentOrderItem['order_item_product_sku'];
                    $productMap['productName'] = $paymentOrderItem['order_item_product_name'];
                    $productMap['taxPaid'] += $taxPaid;
                    $productMap['shippingPaid'] += $shippingPaid;
                    $productMap['financePaid'] += $financePaid;

                    // quantity should never go negative for refunds since we track refunded quantity in another column
                    // we only want to increase the quantity if its the inital payment and not a payment plan renewal
                    if ($paymentData['payment_type'] == Payment::TYPE_INITIAL_ORDER) {
                        $productMap['quantity'] += max($quantity, 0);
                    }

                    if ($productPaid == 0 && $paymentData['payment_type'] == Payment::TYPE_INITIAL_ORDER) {
                        $productMap['freeQuantity'] += max($quantity, 0);
                    }

                    if ($quantity < 0) {
                        $productMap['refundedQuantity'] += abs($quantity);
                    }

                    if ($paymentData['refund_amount'] > 0) {
                        $productMap['refunded'] += $paymentData['refund_amount'] * $orderItemCostRatio;
                    }

                    $productsMap[$paymentOrderItem['order_item_product_id']] = $productMap;
                }

                // testing code
//                if ($paymentData['refund_amount'] == 0 && $paymentData['payment_total_paid'] != round($thisOrderNet, 2)) {
//                    var_dump('Could not fully process total order payment for: ');
//                    var_dump('$paymentData[\'payment_total_paid\']=' . $paymentData['payment_total_paid']);
//                    var_dump('$thisOrderNet=' . $thisOrderNet);
//                    var_dump($paymentData);
//                    var_dump($orderToUse['items']);
//                }
//
//                $handledPaymentIds[] = $paymentData['refund_id'] ?? $paymentData['payment_id'];

                if ($paymentData['refund_amount'] == 0) {
                    $paymentsGrandTotalPaidTracked += $paymentData['payment_total_paid'];
                }

                continue;
            }

            // if there is an attached subscription and it has a specific product attached then its a membership
            // renewal payment so use the subscription to associate
            if (!empty($paymentData['subscription']) &&
                !empty($paymentData['subscription']['subscription_product_sku'])) {
                // calculate refund ratio if applicable
                if ($paymentData['payment_total_paid'] > 0 && $paymentData['refund_amount'] > 0) {
                    $refundRatio = $paymentData['refund_amount'] / $paymentData['payment_total_paid'];
                } else {
                    $refundRatio = 0;
                }

                // use subscription
                $taxPaid = $paymentData['payment_product_taxes_paid'];
                $productPaid = $paymentData['payment_total_paid'] - $taxPaid;
                $quantity = 1;

                // ---------------------------
                // set the product map
                // create the first map array element for this product if its not already created
                $productMap = $productsMap[$paymentData['subscription']['subscription_product_id']] ?? $productMapElementExample;

                // if its a refund we should subtract all the totals instead of adding
                if (!empty($paymentData['refund_amount']) && $paymentData['refund_amount'] > 0) {
                    $productPaid = -$productPaid * $refundRatio;
                    $taxPaid = -$taxPaid * $refundRatio;
                    $quantity = -$quantity * $refundRatio;

                    $productMap['recurringProductPaid'] += ($paymentData['payment_total_paid'] - ($paymentData['payment_total_paid'] * $refundRatio));
                } else {
                    $productMap['recurringProductPaid'] += $paymentData['payment_total_paid'];
                }

                $productMap['productPaid'] += $productPaid;

                $productMap['grossPaid'] += $productPaid;

                if ($paymentData['refund_amount'] > 0) {
                    $productMap['netPaid'] +=
                        -$paymentData['refund_amount'];
                } else {
                    $productMap['netPaid'] +=
                        $paymentData['payment_total_paid'];
                }

                // set product info and rest
                $productMap['productSku'] = $paymentData['subscription']['subscription_product_sku'];
                $productMap['productName'] = $paymentData['subscription']['subscription_product_name'];
                $productMap['taxPaid'] += $taxPaid;

                // quantity should never go negative for refunds since we track refunded quantity in another column
                $productMap['quantity'] += max($quantity, 0);

                if ($quantity < 0) {
                    $productMap['refundedQuantity'] += abs($quantity);
                }

                if ($paymentData['refund_amount'] > 0) {
                    $productMap['refunded'] += $paymentData['refund_amount'];
                }

                $productsMap[$paymentData['subscription']['subscription_product_id']] = $productMap;

                // testing code
//                if ($paymentData['refund_amount'] == 0 && $paymentData['payment_total_paid'] != round($productPaid + $taxPaid, 2)) {
//                    var_dump('Could not fully process total subscription payment for: ');
//                    var_dump('$paymentData[\'payment_total_paid\']=' . $paymentData['payment_total_paid']);
//                    var_dump('$thisOrderNet=' . ($productPaid + $taxPaid));
//                    var_dump($paymentData);
//                }
//
//                $handledPaymentIds[] = $paymentData['refund_id'] ?? $paymentData['payment_id'];

                if ($paymentData['refund_amount'] == 0) {
                    $paymentsGrandTotalPaidTracked += $paymentData['payment_total_paid'];
                }

                continue;
            }

//            var_dump('Cound not handle payment: ' . $paymentData['payment_id']);
//            var_dump($paymentData);
        }

        $totalProduct = 0;
        $totalNet = 0;

        foreach ($productsMap as $productMap) {
            $totalNet += $productMap['netPaid'];
        }

//        var_dump('Total payments and refunds processed: ' . count($handledPaymentIds));
//        var_dump('total paid in DB: ' . $totalPaidDatabase);
//        var_dump('total refunded in DB: ' . $totalRefundedDatabase);
//        var_dump('$totalNet: ' . $totalNet);
//        var_dump('$paymentsGrandTotalPaidTracked: ' . $paymentsGrandTotalPaidTracked);
//
//        dd($productsMap);

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
            $productStatistics->setNetRecurringProduct(round($productData['recurringProductPaid'], 2));
            $productStatistics->setNetProduct(round($productData['productPaid'], 2));
            $productStatistics->setNetPaid(round($productData['netPaid'], 2));

            $result->addProductStatistics($productStatistics);
        }

        // accounting also needs rows with all 0's for products without data
        $allProducts = $this->productRepository->all();

        // if brand is passed in, only add products from that brand
        foreach ($allProducts as $productIndex => $product) {
            if (!empty($brand) && $product->getBrand() != $brand && $product->getBrand() != 'musora') {
                continue;
            }

            if (empty($result->getAccountingProducts()[$product->getId()])) {
                $productStatistics = new AccountingProduct($product->getId());

                $productStatistics->setName($product->getName());
                $productStatistics->setSku($product->getSku());
                $productStatistics->setInventoryControlSku($product->getInventoryControlSku());
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
            } else {
                $result->getAccountingProducts()[$product->getId()]
                    ->setInventoryControlSku($product->getInventoryControlSku());
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
        $result->setFinancePaid(round($result->getFinancePaid(), 2));

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

        // gross recurring product
        $result->setNetRecurringProduct(0);
        foreach ($result->getAccountingProducts() as $accountingProduct) {
            $result->setNetRecurringProduct(
                $result->getNetRecurringProduct() + $accountingProduct->getNetRecurringProduct()
            );
        }
        $result->setNetRecurringProduct(round($result->getNetRecurringProduct(), 2));

        // net paid
        $result->setNetPaid(0);
        foreach ($result->getAccountingProducts() as $accountingProduct) {
            $result->setNetPaid($result->getNetPaid() + $accountingProduct->getNetPaid());
        }
        $result->setNetPaid(round($result->getNetPaid(), 2));

        return $result;
    }
}