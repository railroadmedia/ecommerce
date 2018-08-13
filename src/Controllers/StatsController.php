<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\Decorator;

class StatsController extends BaseController
{
    /**
     * @var PaymentRepository
     */
    private $paymentRepository;
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * StatsController constructor.
     *
     * @param ProductRepository $productRepository
     */
    public function __construct(
        PaymentRepository $paymentRepository,
        ProductRepository $productRepository,
        OrderRepository $orderRepository
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
    }

    public function statsProduct(Request $request)
    {
        $products =
            $this->productRepository->query()
                ->where(
                    ConfigService::$tableProduct . '.brand',
                    $request->get('brand', ConfigService::$brand)
                )
                ->get();

        foreach ($products as $index => $product) {
            //get info from orders
            $orders =
                $this->orderRepository->query()
                    ->select(ConfigService::$tableOrder . '.*', ConfigService::$tableOrderItem . '.total_price')
                    ->join(
                        ConfigService::$tableOrderItem,
                        ConfigService::$tableOrder . '.id',
                        '=',
                        ConfigService::$tableOrderItem . '.order_id'
                    )
                    ->where(
                        ConfigService::$tableOrder . '.created_on',
                        '>',
                        Carbon::parse($request->get('start-date', Carbon::now()))
                            ->startOfDay()
                    )
                    ->where(
                        ConfigService::$tableOrder . '.created_on',
                        '<',
                        Carbon::parse($request->get('end-date', Carbon::now()))
                            ->endOfDay()
                    )
                    ->where(ConfigService::$tableOrderItem . '.product_id', $product->id)
                    ->where(ConfigService::$tableOrderItem . '.total_price', '>', 0)
                    ->get();
            $tax = 0;
            $shippingCosts = 0;
            $refunded = 0;
            $renewal = 0;
            $finance = 0;
            $totalOrderWeight = 0;
            $quantity = 0;
            $paid = 0;

            foreach ($orders as $order) {
                $fullOrder = Decorator::decorate($order, 'order');
                $quantity += $fullOrder->items['quantity'];

                if ($product->weight > 0) {
                    $totalOrderWeight += $fullOrder->items['product']['weight'];
                    $shippingCosts += $product->weight / $totalOrderWeight * $order->shipping_costs;
                }

                if ($order->paid > 0) {
                    $tax += $order->total_price / $order->paid * $order->tax;

                    //get order payment details
                    $orderPayments =
                        $this->paymentRepository->query()
                            ->join(
                                ConfigService::$tableOrderPayment,
                                ConfigService::$tablePayment . '.id',
                                '=',
                                ConfigService::$tableOrderPayment . '.payment_id'
                            )
                            ->where(ConfigService::$tableOrderPayment . '.order_id', $order->id)
                            ->get();
                    $totalOrderRefunded = $orderPayments->sum('refunded');
                    $refunded += $order->total_price / $order->paid * $totalOrderRefunded;
                }

                $paid += (($fullOrder->paid / $fullOrder->due * $fullOrder->items['total_price']) +
                    $shippingCosts +
                    $tax);
            }

            //get subscriptions stats
            $subscriptions =
                $this->paymentRepository->query()
                    ->select(
                        ConfigService::$tablePayment . '.id',
                        ConfigService::$tablePayment . '.paid as payment_paid',
                        ConfigService::$tablePayment . '.type as payment_type',
                        ConfigService::$tablePayment . '.refunded',
                        ConfigService::$tablePayment . '.payment_method_id',
                        ConfigService::$tableSubscription . '.order_id',
                        ConfigService::$tableSubscription . '.tax_per_payment',
                        ConfigService::$tableSubscription . '.type as subscription_type'
                    )
                    ->join(
                        ConfigService::$tableSubscriptionPayment,
                        ConfigService::$tablePayment . '.id',
                        '=',
                        ConfigService::$tableSubscriptionPayment . '.payment_id'
                    )
                    ->join(
                        ConfigService::$tableSubscription,
                        ConfigService::$tableSubscriptionPayment . '.subscription_id',
                        '=',
                        ConfigService::$tableSubscription . '.id'
                    )
                    ->where(
                        ConfigService::$tablePayment . '.created_on',
                        '>',
                        Carbon::parse($request->get('start-date', Carbon::now()))
                            ->startOfDay()
                    )
                    ->where(
                        ConfigService::$tablePayment . '.created_on',
                        '<',
                        Carbon::parse($request->get('end-date', Carbon::now()))
                            ->endOfDay()
                    )
                    ->whereIn(ConfigService::$tablePayment . '.type', [ConfigService::$renewalPaymentType])
                    ->whereIn(ConfigService::$tablePayment . '.status', ['succeeded', 'paid', 1])
                    ->where(ConfigService::$tableSubscription . '.product_id', $product->id)
                    ->get();
            foreach ($subscriptions as $subscription) {
                $quantity++;
                $paid += $subscription->payment_paid;
                $refunded += $subscription->refunded;
                $tax += $subscription->tax_per_payment;
                $shippingCosts += $subscription->shipping_per_payment;
                $renewal++;
            }

            //get payment plans renewal stats
            $paymentPlans =
                $this->paymentRepository->query()
                    ->select(
                        ConfigService::$tablePayment . '.id',
                        ConfigService::$tablePayment . '.paid as payment_paid',
                        ConfigService::$tablePayment . '.type as payment_type',
                        ConfigService::$tablePayment . '.refunded',
                        ConfigService::$tablePayment . '.payment_method_id',
                        ConfigService::$tableSubscription . '.order_id',
                        ConfigService::$tableSubscription . '.tax_per_payment',
                        ConfigService::$tableSubscription . '.type as subscription_type'
                    )
                    ->join(
                        ConfigService::$tableSubscriptionPayment,
                        ConfigService::$tablePayment . '.id',
                        '=',
                        ConfigService::$tableSubscriptionPayment . '.payment_id'
                    )
                    ->join(
                        ConfigService::$tableSubscription,
                        ConfigService::$tableSubscriptionPayment . '.subscription_id',
                        '=',
                        ConfigService::$tableSubscription . '.id'
                    )
                    ->join(
                        ConfigService::$tableOrderItem,
                        ConfigService::$tableSubscription . '.order_id',
                        '=',
                        ConfigService::$tableOrderItem . '.order_id'
                    )
                    ->where(
                        ConfigService::$tablePayment . '.created_on',
                        '>',
                        Carbon::parse($request->get('start-date', Carbon::now()))
                            ->startOfDay()
                    )
                    ->where(
                        ConfigService::$tablePayment . '.created_on',
                        '<',
                        Carbon::parse($request->get('end-date', Carbon::now()))
                            ->endOfDay()
                    )
                    ->whereIn(ConfigService::$tablePayment . '.type', [ConfigService::$renewalPaymentType])
                    ->whereIn(ConfigService::$tablePayment . '.status', ['succeeded', 'paid', 1])
                    ->where(ConfigService::$tableSubscription . '.brand', $request->get('brand', ConfigService::$brand))
                    ->whereNull(ConfigService::$tableSubscription . '.product_id')
                    ->where(ConfigService::$tableOrderItem . '.product_id', $product->id)
                    ->get();

            foreach ($paymentPlans as $paymentPlan) {
                $quantity++;
                $paid += $paymentPlan->payment_paid;
                $refunded += $paymentPlan->refunded;
                $tax += $paymentPlan->tax_per_payment;
                $shippingCosts += $paymentPlan->shipping_per_payment;
                $renewal++;
                $finance++;
            }

            $products[$index]['quantity'] = $quantity;
            $products[$index]['paid'] = $paid;
            $products[$index]['tax'] = $tax;
            $products[$index]['shippingCosts'] = $shippingCosts;
            $products[$index]['refunded'] = $refunded;
            $products[$index]['renewal'] = $renewal;
            $products[$index]['finance'] = $finance;

        }

        return reply()->json($products);
    }
}