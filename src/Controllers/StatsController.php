<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Repositories\AddressRepository;
use Railroad\Ecommerce\Repositories\CustomerRepository;
use Railroad\Ecommerce\Repositories\OrderItemRepository;
use Railroad\Ecommerce\Repositories\OrderPaymentRepository;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\Decorator;
use Railroad\Resora\Entities\Entity;
use Railroad\Usora\Repositories\UserRepository;

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
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var AddressRepository
     */
    private $addressRepository;

    /**
     * @var PaymentMethodRepository
     */
    private $paymentMethodRepository;

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
        SubscriptionRepository $subscriptionRepository,
        CustomerRepository $customerRepository,
        UserRepository $userRepository,
        AddressRepository $addressRepository,
        PaymentMethodRepository $paymentMethodRepository
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->customerRepository = $customerRepository;
        $this->userRepository = $userRepository;
        $this->addressRepository = $addressRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    public function statsProduct(Request $request)
    {
        $products =
            $this->productRepository->query()
                ->select('id', 'sku', 'name', 'type', 'is_physical', 'weight')
                ->selectRaw(
                    '0 as paid, 0 as shippingCosts, 0 as finance, 0 as tax, 0 as refunded, 0 as quantity, 0 as totalNet'
                )
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
            //order stats
            $orders =
                $this->orderRepository->query()
                    ->whereIn('id', $orderPayments->pluck('order_id'))
                    ->where(
                        ConfigService::$tableOrder . '.brand',
                        $request->get('brand', ConfigService::$brand)
                    )
                    ->orderBy('created_on')
                    ->chunk(
                        250,
                        function ($orders) use (&$products, &$orderPaymentDetails, &$orderPayments) {

                            $orderItems =
                                $this->orderItemRepository->query()
                                    ->whereIn('order_id', $orders->pluck('id'))
                                    ->get()
                                    ->groupBy('order_id');

                            foreach ($orders as $order) {
                                $items = $orderItems[$order->id];
                                $orderTotalWeight = $items->sum('product.weight');
                                $orderTotalPaid = $items->sum('total_price');
                                $payment = $orderPaymentDetails[$orderPayments[$order->id]->payment_id];

                                foreach ($items as $index => $item) {
                                    $quantity = $products[$item->product_id]->quantity;
                                    $shippingCosts = $products[$item->product_id]->shippingCosts;
                                    $refunded = $products[$item->product_id]->refunded;
                                    $paid = $products[$item->product_id]->paid;
                                    $finance = $products[$item->product_id]->finance;
                                    $tax = $products[$item->product_id]->tax;
                                    if ($orderTotalPaid > 0) {
                                        $paid += $item->total_price / $orderTotalPaid * $payment->paid;
                                    } else {
                                        $paidForThisOrderItem = $payment->paid / count($items);

                                        $paid += $paidForThisOrderItem;
                                    }
                                    if ($payment->refunded == 0) {
                                        if (($products[$item->product_id]->is_physical == 1) &&
                                            ($orderTotalWeight > 0)) {
                                            $shippingCosts += $products[$item->product_id]->weight /
                                                $orderTotalWeight *
                                                $order->shipping_costs;
                                        }
                                        $quantity += $item->quantity;
                                        if ($orderTotalPaid > 0) {
                                            $tax += $item->total_price /
                                                $orderTotalPaid *
                                                ($order->tax * $payment->paid / $order->due);
                                        } else {
                                            $tax += $order->tax / count($items);
                                        }
                                        //finance
                                        if (($index == 0) && ($order->paid < $order->due) && ($order->due > 0)) {
                                            $finance++;
                                        }
                                    }
                                    if (($index == 0) && ($payment->refunded > 0)) {
                                        $refunded += $payment->refunded;
                                    }

                                    $products[$item->product_id]->offsetSet('quantity', $quantity);
                                    $products[$item->product_id]->offsetSet('shippingCosts', $shippingCosts);
                                    $products[$item->product_id]->offsetSet('tax', $tax);
                                    $products[$item->product_id]->offsetSet('refunded', $refunded);
                                    $products[$item->product_id]->offsetSet('paid', $paid);
                                    $products[$item->product_id]->offsetSet('finance', $finance);
                                    $products[$item->product_id]->offsetSet('totalNet', ($paid - $refunded));
                                }
                            }

                        }
                    );
        }

        //renewal stats
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
                    ->orderBy('created_on')
                    ->chunk(
                        250,
                        function ($subscriptions) use (
                            &$products,
                            &$subscriptionRenewalPayments,
                            &
                            $subscriptionPaymentDetails
                        ) {
                            foreach ($subscriptions as $subscription) {
                                if ($subscription->type == ConfigService::$typeSubscription) {
                                    $payment =
                                        $subscriptionPaymentDetails[$subscriptionRenewalPayments[$subscription->id]->payment_id];

                                    if ($payment->refunded == 0) {

                                        $shippingCosts =
                                            $products[$subscription->product_id]->shippingCosts +
                                            $subscription->shipping_per_payment;
                                        $tax =
                                            $products[$subscription->product_id]->tax + $subscription->tax_per_payment;

                                        $products[$subscription->product_id]->offsetSet(
                                            'shippingCosts',
                                            $shippingCosts
                                        );
                                        $products[$subscription->product_id]->offsetSet('tax', $tax);

                                    }

                                    $refunded = $products[$subscription->product_id]->refunded + $payment->refunded;

                                    $paid = $products[$subscription->product_id]->paid + $payment->paid;
                                    if ($subscription->product_id == 133) {
                                        var_dump('add refunded si results::' . $refunded);
                                    }
                                    $products[$subscription->product_id]->offsetSet('refunded', $refunded);
                                    $products[$subscription->product_id]->offsetSet('paid', $paid);
                                    $products[$subscription->product_id]->offsetSet('totalNet', ($paid - $refunded));
                                } else {
                                    if ($subscription->order_id) {
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
                                        $orderTotalPaid =
                                            $itemsForPaymentPlans[$orderPaymentPlan->id]->sum('total_price');
                                        $payment =
                                            $subscriptionPaymentDetails[$subscriptionRenewalPayments[$subscription->id]->payment_id];
                                        $items = $itemsForPaymentPlans[$orderPaymentPlan->id];
                                        foreach ($items as $index => $item) {
                                            $quantity = $products[$item->product_id]->quantity;
                                            $refunded = $products[$item->product_id]->refunded;
                                            if ($index == 0) {
                                                $refunded += $payment->refunded;

                                            }
                                            $paid = $products[$item->product_id]->paid;
                                            if ($orderTotalPaid > 0) {
                                                $paid += $item->total_price / $orderTotalPaid * $payment->paid;
                                            } else {
                                                $paid += $payment->paid / count($items);
                                            }

                                            if ($payment->refunded == 0) {
                                                $quantity += $item->quantity;
                                                $tax =
                                                    $products[$item->product_id]->tax +
                                                    $subscriptions->whereStrict('order_id', $orderPaymentPlan->id)
                                                        ->sum('tax_per_payment');

                                                $products[$item->product_id]->offsetSet('quantity', $quantity);
                                                $products[$item->product_id]->offsetSet('tax', $tax);
                                            }
                                            $products[$item->product_id]->offsetSet('paid', $paid);
                                            $products[$item->product_id]->offsetSet('refunded', $refunded);
                                            $products[$item->product_id]->offsetSet('totalNet', ($paid - $refunded));
                                        }
                                    }
                                }

                            }
                        }
                    );
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
        $products->push(
            new Entity(
                [
                    'name' => 'unknown (old payment plans)',
                    'sku' => 'unknown',
                    'quantity' => $quantity,
                    'paid' => $paid,
                    'refunded' => $refunded,
                    'tax' => $tax,
                    'shippingCosts' => $shippingCosts,
                    'finance' => 0,
                    'totalNet' => ($paid - $refunded),
                ]
            )
        );

        $results = new Entity(
            [
                'productStats' => $products,
                'totalPaid' => $products->sum('paid'),
                'totalRefunded' => $products->sum('refunded'),
                'totalShipping' => $products->sum('shippingCosts'),
                'totalFinance' => $products->sum('finance'),
                'totalTax' => $products->sum('tax'),
                'totalNet' => $products->sum('totalNet'),
            ]
        );
        return reply()->json($results);
    }

    public function statsOrder(Request $request)
    {
        $rows = [];

        $rowDataTemplate = [
            'email' => '',
            'address' => '',
            'name' => '',
            'products' => '',
            'net paid' => 0.0,
            'shipping paid' => 0.0,
            'tax paid' => 0.0,
            'finance paid' => 0.0,
            'total paid' => 0.0,
        ];

        $products =
            $this->productRepository->query()
                ->select('id', 'sku', 'name', 'type', 'is_physical', 'weight')
                ->selectRaw(
                    '0 as paid, 0 as shippingCosts, 0 as finance, 0 as tax, 0 as refunded, 0 as quantity, 0 as totalNet'
                )
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
                ->orderBy('created_on')
                ->chunk(
                    100,
                    function ($allPayments) use ($request, $products, $rowDataTemplate, &$rows) {
                        $paymentsForOrders = $allPayments->where('type', ConfigService::$orderPaymentType);

                        $orderPayments =
                            $this->orderPaymentRepository->query()
                                ->whereIn('payment_id', $paymentsForOrders->pluck('id'))
                                ->get()
                                ->keyBy('order_id');

                        $orderPaymentDetails =
                            $this->paymentRepository->query()
                                ->whereIn('id', $orderPayments->pluck('payment_id'))
                                ->get()
                                ->keyBy('id');
                        //order stats
                        $orders =
                            $this->orderRepository->query()
                                ->whereIn('id', $orderPayments->pluck('order_id'))
                                ->where(
                                    ConfigService::$tableOrder . '.brand',
                                    $request->get('brand', ConfigService::$brand)
                                )
                                ->orderBy('created_on')
                                ->chunk(
                                    250,
                                    function ($orders) use (
                                        $products,
                                        $orderPaymentDetails,
                                        $orderPayments,
                                        $rowDataTemplate,
                                        &$rows
                                    ) {
                                        $orderItems =
                                            $this->orderItemRepository->query()
                                                ->whereIn('order_id', $orders->pluck('id'))
                                                ->get()
                                                ->groupBy('order_id');
                                        $customers =
                                            $this->customerRepository->query()
                                                ->whereIn('id', $orders->pluck('customer_id'))
                                                ->get()
                                                ->groupBy('id');
                                        $users =
                                            $this->userRepository->continueOrNewQuery()
                                                ->whereIn('id', $orders->pluck('user_id'))
                                                ->get()
                                                ->keyBy('id');

                                        $shippingAddresses =
                                            $this->addressRepository->query()
                                                ->whereIn(
                                                    'id',
                                                    // array_merge(
                                                    $orders->pluck('shipping_address_id')
                                                )
                                                ->get()
                                                ->keyBy('id');

                                        foreach ($orders as $order) {
                                            $dataRow = $rowDataTemplate;
                                            $dataRow['type'] = 'order';
                                            $items = [];
                                            if ($orderItems->has($order->id)) {
                                                $items = $orderItems[$order->id];

                                            }
                                            $totalPaidThisOrder =
                                                $orderPaymentDetails[$orderPayments[$order->id]->payment_id]->paid;
                                            $totalRefunded =
                                                $orderPaymentDetails[$orderPayments[$order->id]->payment_id]->refunded;
                                            foreach ($items as $item) {
                                                $dataRow['products'] .= $item->product['name'] .
                                                    ' - ' .
                                                    $item->quantity .
                                                    '<br>';
                                            }
                                            if (!empty($users[$order->user_id]->email)) {
                                                $dataRow['email'] = $users[$order->user_id]->email;
                                            } elseif (!empty($customers[$order->customer_id]->email)) {
                                                $dataRow['email'] = $customers[$order->customer_id]->email;
                                            } else {
                                                $dataRow['email'] = 'unknown';
                                            }

                                            $region = '';
                                            if (!empty($shippingAddresses[$order->shipping_address_id]) &&
                                                !empty($shippingAddresses[$order->shipping_address_id]['country'])) {
                                                $region = $shippingAddresses[$order->shipping_address_id]['state'];

                                                $dataRow['name'] =
                                                    $shippingAddresses[$order->shipping_address_id]['first_name'] .
                                                    ' ' .
                                                    $shippingAddresses[$order->shipping_address_id]['last_name'];

                                                $dataRow['address'] =
                                                    $shippingAddresses[$order->shipping_address_id]['country'] .
                                                    ' - ' .
                                                    $region .
                                                    ' - ' .
                                                    $shippingAddresses[$order->shipping_address_id]['city'] .
                                                    ' - ' .
                                                    $shippingAddresses[$order->shipping_address_id]['street_line_1'] .
                                                    ' - ' .
                                                    $shippingAddresses[$order->shipping_address_id]['street_line_2'];
                                            }

                                            $dataRow['total paid'] = $totalPaidThisOrder;
                                            if ($totalRefunded == 0 && $order->due > 0) {
                                                // tax
                                                $dataRow['tax paid'] = $order->tax * $totalPaidThisOrder / $order->due;

                                                // Shipping
                                                $dataRow['shipping paid'] = $order->shipping_costs;

                                                // Finance
                                                if ($order->paid < $order->due &&
                                                    $order->due > 0 &&
                                                    $totalRefunded == 0) {
                                                    $dataRow['finance paid'] = 1;
                                                }
                                            }

                                            $dataRow['net paid'] = max(
                                                $totalPaidThisOrder -
                                                $dataRow['tax paid'] -
                                                $dataRow['shipping paid'] -
                                                $dataRow['finance paid'] -
                                                $totalRefunded,
                                                0
                                            );

                                            $rows[] = $dataRow;
                                        }

                                    }
                                );
                        $paymentsForSubscriptionsAndPaymentPlans = $allPayments;

                        $subscriptionRenewalPayments =
                            $this->subscriptionPaymentRepository->query()
                                ->whereIn('payment_id', $paymentsForSubscriptionsAndPaymentPlans->pluck('id'))
                                ->get();
                        $subscriptionPaymentDetails =
                            $this->paymentRepository->query()
                                ->whereIn('id', $subscriptionRenewalPayments->pluck('payment_id'))
                                ->get()
                                ->keyBy('id');
                        $subscriptions =
                            $this->subscriptionRepository->query()
                                ->whereIn('id', $subscriptionRenewalPayments->pluck('subscription_id'))
                                ->orderBy('created_on')
                                ->chunk(
                                    250,
                                    function ($subscriptions) use (
                                        &$products,
                                        &$subscriptionRenewalPayments,
                                        &
                                        $subscriptionPaymentDetails,
                                        &$rows,
                                        $rowDataTemplate
                                    ) {
                                        $customers =
                                            $this->customerRepository->query()
                                                ->whereIn('id', $subscriptions->pluck('customer_id'))
                                                ->get()
                                                ->groupBy('id');
                                        $users =
                                            $this->userRepository->continueOrNewQuery()
                                                ->whereIn('id', $subscriptions->pluck('user_id'))
                                                ->get()
                                                ->keyBy('id');
                                        $subscriptionRenewalPayments =
                                            $subscriptionRenewalPayments->keyBy('subscription_id');
                                        foreach ($subscriptions as $subscription) {
                                            if ($subscription->type == ConfigService::$typeSubscription) {
                                                $payment =
                                                    $subscriptionPaymentDetails[$subscriptionRenewalPayments[$subscription->id]['payment_id']];
                                                $dataRow = $rowDataTemplate;
                                                $dataRow['type'] = 'subscription';
                                                $dataRow['products'] .= $products[$subscription->product_id]->name;
                                                $dataRow['total paid'] += $payment->paid;

                                                if (!empty($users[$subscription->user_id]->email)) {
                                                    $dataRow['email'] = $users[$subscription->user_id]->email;
                                                } elseif (!empty($customers[$subscription->customer_id]->email)) {
                                                    $dataRow['email'] = $customers[$subscription->customer_id]->email;
                                                } else {
                                                    $dataRow['email'] = 'unknown';
                                                }
                                                $dataRow['tax paid'] = $subscription->tax_per_payment;
                                                $dataRow['net paid'] =
                                                    $dataRow['total paid'] - $dataRow['tax paid'] - $payment->refunded;
                                            } else {
                                                $dataRow = $rowDataTemplate;
                                                $dataRow['type'] = 'payment plan';
                                                $payment =
                                                    $subscriptionPaymentDetails[$subscriptionRenewalPayments[$subscription->id]['payment_id']];
                                                if ($subscription->order_id) {
                                                    $orderForPaymentPlansRenewed =
                                                        $this->orderRepository->query()
                                                            ->whereIn('id', [$subscription->order_id])
                                                            ->get()
                                                            ->keyBy('id');
                                                    $itemsForPaymentPlans =
                                                        $this->orderItemRepository->query()
                                                            ->whereIn('order_id', [$subscription->order_id])
                                                            ->get();
                                                    foreach ($itemsForPaymentPlans as $item) {

                                                        $dataRow['products'] .= $item->product['name'] .
                                                            ' - ' .
                                                            $item->quantity .
                                                            '<br>';
                                                    }
                                                    $dataRow['tax paid'] =
                                                        $orderForPaymentPlansRenewed[$subscription->order_id]->tax *
                                                        $payment->paid /
                                                        $orderForPaymentPlansRenewed[$subscription->order_id]->due;
                                                    $dataRow['total paid'] = $payment->paid;
                                                    $dataRow['net paid'] =
                                                        $dataRow['total paid'] -
                                                        $dataRow['tax paid'] -
                                                        $payment->refunded;
                                                }
                                            }

                                            $rows[] = $dataRow;
                                        }
                                    }
                                );
                    }
                );
        $rowTotals = [
            'net paid' => array_sum(array_pluck($rows,'net paid')),
            'shipping paid' => array_sum(array_pluck($rows,'shipping paid')),
            'finance paid' => array_sum(array_pluck($rows,'finance paid')),
            'tax paid' => array_sum(array_pluck($rows,'tax paid')),
            'total paid' => array_sum(array_pluck($rows,'total paid')),
        ];
        $results = new Entity(
            [
                'rows' => $rows,
                'totalRows' => $rowTotals,
            ]
        );
        return reply()->json($results);
    }
}