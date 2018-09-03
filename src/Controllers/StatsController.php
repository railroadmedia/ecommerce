<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Repositories\OrderItemRepository;
use Railroad\Ecommerce\Repositories\OrderPaymentRepository;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\Decorator;
use Railroad\Resora\Entities\Entity;

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
     * @var OrderItemRepository
     */
    private $orderItemRepository;

    /**
     * @var OrderPaymentRepository
     */
    private $orderPaymentRepository;

    /**
     * @var SubscriptionPaymentRepository
     */
    private $subscriptionPaymentRepository;

    /**
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * StatsController constructor.
     *
     * @param ProductRepository $productRepository
     */
    public function __construct(
        PaymentRepository $paymentRepository,
        ProductRepository $productRepository,
        OrderRepository $orderRepository,
        OrderItemRepository $orderItemRepository,
        OrderPaymentRepository $orderPaymentRepository,
        SubscriptionPaymentRepository $subscriptionPaymentRepository,
        SubscriptionRepository $subscriptionRepository
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->subscriptionRepository = $subscriptionRepository;
    }

    public function statsProduct(Request $request)
    {
        $orderPayments = [];
        $orderPaymentDetails = [];
        $subscriptions = [
            ConfigService::$typeSubscription => collect([]),
            ConfigService::$paymentPlanType => collect([]),

        ];
        $orders = [];
        $orderItems = [];

        $products =
            $this->productRepository->query()
                ->select('id', 'sku', 'name', 'type', 'is_physical', 'weight')
                ->selectRaw('0 as quantity, 0 as paid, 0 as tax, 0 as refunded, 0 as shippingCosts,  0 as finance')
                ->where(
                    ConfigService::$tableProduct . '.brand',
                    $request->get('brand', ConfigService::$brand)
                )
                ->get()
                ->keyBy('id');

        $allPayments =
            $this->paymentRepository->query()
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
                ->whereIn(ConfigService::$tablePayment . '.status', ['paid', 1])
                ->where(ConfigService::$tablePayment . '.paid', '>', 0)
                ->get()
                ->groupBy('type');
        if ($allPayments->has(ConfigService::$orderPaymentType)) {
            $orderPayments =
                $this->orderPaymentRepository->query()
                    ->whereIn('payment_id', $allPayments[ConfigService::$orderPaymentType]->pluck('id'))
                    ->get()
                    ->keyBy('order_id');
            $orderPaymentDetails =
                $this->paymentRepository->query()
                    ->whereIn('id', $orderPayments->pluck('payment_id'))
                    ->get()
                    ->keyBy('id');
            $orders =
                $this->orderRepository->query()
                    ->whereIn('id', $orderPayments->pluck('order_id'))
                    ->where(
                        ConfigService::$tableOrder . '.brand',
                        $request->get('brand', ConfigService::$brand)
                    )
                    ->get();
            $orderItems =
                $this->orderItemRepository->query()
                    ->whereIn('order_id', $orders->pluck('id'))
                    ->get()
                    ->groupBy('order_id');
        }
        if ($allPayments->has(ConfigService::$renewalPaymentType)) {
            $subscriptionRenewalPayments =
                $this->subscriptionPaymentRepository->query()
                    ->whereIn('payment_id', $allPayments[ConfigService::$renewalPaymentType]->pluck('id'))
                    ->get()
                    ->keyBy('subscription_id');
            $subscriptionPaymentDetails =
                $this->paymentRepository->query()
                    ->whereIn('id', $subscriptionRenewalPayments->pluck('payment_id'))
                    ->get()
                    ->keyBy('id');
            $subscriptions =
                $this->subscriptionRepository->query()
                    ->whereIn('id', $subscriptionRenewalPayments->pluck('subscription_id'))
                    ->get()
                    ->groupBy('type');
        }

        //order stats
        foreach ($orders as $order) {
            $items = $orderItems[$order->id];
            $orderTotalWeight = $items->sum('product.weight');
            $orderTotalPaid = $items->sum('total_price');
            $payment = $orderPaymentDetails[$orderPayments[$order->id]->payment_id];

            foreach ($items as $item) {
                $quantity = $products[$item->product_id]->quantity;
                $shippingCosts = $products[$item->product_id]->shippingCosts;
                $tax = $products[$item->product_id]->tax;
                $refunded = $products[$item->product_id]->refunded;
                $paid = $products[$item->product_id]->paid;
                if ($payment->refunded == 0) {
                    if (($products[$item->product_id]->is_physical == 1) && ($orderTotalWeight > 0)) {
                        $shippingCosts += $products[$item->product_id]->weight /
                            $orderTotalWeight *
                            $order->shipping_costs;
                    }
                    $quantity += $item->quantity;
                    $tax += $item->total_price / $order->paid * $order->tax;
                    $paid += $item->total_price/$orderTotalPaid * $payment->paid;
                }
                $refunded += $item->total_price / $order->paid * $payment->refunded;

                $products[$item->product_id]->offsetSet('quantity', $quantity);
                $products[$item->product_id]->offsetSet('shippingCosts', $shippingCosts);
                $products[$item->product_id]->offsetSet('tax', $tax);
                $products[$item->product_id]->offsetSet('refunded', $refunded);
                $products[$item->product_id]->offsetSet('paid', $paid);

            }
        }

        //renewal stats
        $subscriptionRenewed = $subscriptions[ConfigService::$typeSubscription];
        foreach ($subscriptionRenewed as $subscription) {
            $payment = $subscriptionPaymentDetails[$subscriptionRenewalPayments[$subscription->id]->payment_id];
            $orderTotalPaid = $items->sum('total_price');
            if ($payment->refunded == 0) {
                $quantity = $products[$subscription->product_id]->quantity + 1;
                $shippingCosts = $products[$subscription->product_id]->shippingCosts + $subscription->shipping_costs;
                $tax = $products[$subscription->product_id]->tax+ $subscription->tax_per_payment;
                $paid = $products[$subscription->product_id]->paid + $subscription->total_price_per_payment;
            }

            $refunded = $products[$item->product_id]->refunded + $payment->refunded;
            $products[$subscription->product_id]->offsetSet('quantity', $quantity);
            $products[$subscription->product_id]->offsetSet('shippingCosts', $shippingCosts);
            $products[$subscription->product_id]->offsetSet('tax', $tax);
            $products[$subscription->product_id]->offsetSet('refunded', $refunded);
            $products[$subscription->product_id]->offsetSet('paid', $paid);
        }

        $paymentPlansRenewed = $subscriptions[ConfigService::$paymentPlanType];
        foreach ($paymentPlansRenewed as $subscription) {
            if($subscription->order_id) {
                $orderForPaymentPlansRenewed =
                    $this->orderRepository->query()
                        ->whereIn('id', [$subscription->order_id])
                        ->get();
                $itemsForPaymentPlans =
                    $this->orderItemRepository->query()
                        ->whereIn('order_id', [$subscription->order_id])
                        ->get()
                        ->groupBy('order_id');
            }

            foreach ($orderForPaymentPlansRenewed as $orderPaymentPlan) {
                $orderTotalPaid = $itemsForPaymentPlans[$orderPaymentPlan->id]->sum('total_price');
                $payment = $subscriptionPaymentDetails[$subscriptionRenewalPayments[$subscription->id]->payment_id];
                $items = $itemsForPaymentPlans[$orderPaymentPlan->id];
                foreach ($items as $item) {
                    $quantity = $products[$item->product_id]->quantity;
                    $paid = $products[$item->product_id]->paid;
                    if ($payment->refunded == 0) {
                        $quantity += $item->quantity;
                        $shippingCosts =
                            $products[$item->product_id]->shippingCosts +
                            $paymentPlansRenewed->whereStrict('order_id', $orderPaymentPlan->id)
                                ->sum('shipping_per_payment');
                        $tax =
                            $products[$item->product_id]->tax +
                            $paymentPlansRenewed->whereStrict('order_id', $orderPaymentPlan->id)
                                ->sum('tax_per_payment');
                        $paid += $item->total_price/$orderTotalPaid * $payment->paid;
                        $products[$item->product_id]->offsetSet('quantity', $quantity);
                        $products[$item->product_id]->offsetSet('shippingCosts', $shippingCosts);

                        $products[$item->product_id]->offsetSet('tax', $tax);
                        $products[$item->product_id]->offsetSet('paid', $paid);
                    }
                }
            }
        }
   
        //unknown plans
        //get subscriptions stats
        $unknownPlans =
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
                ->whereIn(ConfigService::$tablePayment . '.status', ['succeeded', 'paid', 1])
                ->whereNull(ConfigService::$tableSubscription . '.product_id')
                ->whereNull(ConfigService::$tableSubscription . '.order_id')
                ->get();
        $quantity = 0;
        $paid = 0;
        $refunded = 0;
        $tax = 0;
        $shippingCosts = 0;

        foreach ($unknownPlans as $unknownPlan) {
            $quantity++;
            $paid += $unknownPlan->payment_paid;
            $refunded += $unknownPlan->refunded;
            $tax += $unknownPlan->tax_per_payment;
            $shippingCosts += $unknownPlan->shipping_per_payment;
        }
        $products[count($products)] = new Entity(
            [
                'name' => 'unknown (old payment plans)',
                'sku' => 'unknown',
                'quantity' => $quantity,
                'paid' => $paid,
                'refunded' => $refunded,
                'tax' => $tax,
                'shippingCosts' => $shippingCosts,
                'finance' => 0,
            ]
        );

        return reply()->json($products);
    }
}