<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Entities\Product;
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
use Railroad\Permissions\Services\PermissionService;
use Railroad\Resora\Decorators\Decorator;
use Railroad\Resora\Entities\Entity;

class StatsController extends Controller
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
     * @var PermissionService
     */
    private $permissionService;

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
        AddressRepository $addressRepository,
        PaymentMethodRepository $paymentMethodRepository,
        PermissionService $permissionService
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->addressRepository = $addressRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->permissionService = $permissionService;
    }

    public function statsProduct(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.stats');

        $products =
            $this->productRepository->query()
                ->select('id', 'sku', 'name', 'type', 'is_physical', 'weight')
                ->selectRaw(
                    '0 as paid, 0 as shippingCosts, 0 as finance, 0 as tax, 0 as refunded, 0 as quantity, 0 as totalNet'
                )
                ->whereIn(
                    'ecommerce_products' . '.brand',
                    $request->get('brands', [config('ecommerce.available_brands')])
                )
                ->get()
                ->keyBy('id');

        $allPayments =
            $this->paymentRepository->query()
                ->where(
                    'ecommerce_order_payments' . '.created_on',
                    '>',
                    Carbon::parse($request->get('start-date', Carbon::now()))
                        ->startOfDay()
                )
                ->where(
                    'ecommerce_order_payments' . '.created_on',
                    '<',
                    Carbon::parse($request->get('end-date', Carbon::now()))
                        ->endOfDay()
                )
                ->whereIn('ecommerce_order_payments' . '.status', ['paid', 1])
                ->where('ecommerce_order_payments' . '.paid', '>', 0)
                ->get()
                ->groupBy('type');
        if ($allPayments->has(config('ecommerce.order_payment_type'))) {
            $orderPayments =
                $this->orderPaymentRepository->query()
                    ->whereIn('payment_id', $allPayments[config('ecommerce.order_payment_type')]->pluck('id'))
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
                    ->whereIn(
                        'ecommerce_orders' . '.brand',
                        $request->get('brands', [config('ecommerce.available_brands')])
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
        if ($allPayments->has(config('ecommerce.renewal_payment_type'))) {
            $subscriptionRenewalPayments =
                $this->subscriptionPaymentRepository->query()
                    ->whereIn('payment_id', $allPayments[config('ecommerce.renewal_payment_type')]->pluck('id'))
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
                    ->whereIn('brand', $request->get('brands', [config('ecommerce.available_brands')]))
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
                                if ($subscription->type == Product::TYPE_SUBSCRIPTION) {
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
                    'ecommerce_order_payments' . '.id',
                    'ecommerce_order_payments' . '.paid as payment_paid',
                    'ecommerce_order_payments' . '.type as payment_type',
                    'ecommerce_order_payments' . '.refunded',
                    'ecommerce_order_payments' . '.payment_method_id',
                    'ecommerce_subscriptions' . '.order_id',
                    'ecommerce_subscriptions' . '.tax_per_payment',
                    'ecommerce_subscriptions' . '.type as subscription_type'
                )
                ->join(
                    'ecommerce_subscription_payments',
                    'ecommerce_order_payments' . '.id',
                    '=',
                    'ecommerce_subscription_payments' . '.payment_id'
                )
                ->join(
                    'ecommerce_subscriptions',
                    'ecommerce_subscription_payments' . '.subscription_id',
                    '=',
                    'ecommerce_subscriptions' . '.id'
                )
                ->where(
                    'ecommerce_order_payments' . '.created_on',
                    '>',
                    Carbon::parse($request->get('start-date', Carbon::now()))
                        ->startOfDay()
                )
                ->where(
                    'ecommerce_order_payments' . '.created_on',
                    '<',
                    Carbon::parse($request->get('end-date', Carbon::now()))
                        ->endOfDay()
                )
                ->whereIn('ecommerce_order_payments' . '.status', ['succeeded', 'paid', 1])
                ->whereNull('ecommerce_subscriptions' . '.product_id')
                ->whereNull('ecommerce_subscriptions' . '.order_id')
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
        $this->permissionService->canOrThrow(auth()->id(), 'pull.stats');

        $brand = $request->get('brands', [config('ecommerce.available_brands')]);
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

        $alreadyCalculatedPaymentIds = [];

        $this->paymentRepository->query()
            ->where(
                'ecommerce_order_payments' . '.created_on',
                '>',
                Carbon::parse($request->get('start-date', Carbon::now()))
                    ->startOfDay()
            )
            ->where(
                'ecommerce_order_payments' . '.created_on',
                '<',
                Carbon::parse($request->get('end-date', Carbon::now()))
                    ->endOfDay()
            )
            ->whereIn(
                'ecommerce_order_payments' . '.status',
                ['paid', 1]
            )
            ->orderBy('created_on')
            ->chunk(
                100,
                function ($allPayments) use ($rowDataTemplate, &$rows, &$alreadyCalculatedPaymentIds, $brand) {
                    $paymentMethods =
                        $this->paymentMethodRepository->query()
                            ->whereIn('id', $allPayments->pluck('payment_method_id'))
                            ->get()
                            ->keyBy('id');

                    $paymentsForOrders = $allPayments->where('type', config('ecommerce.order_payment_type'));

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
                    $this->orderRepository->query()
                        ->whereIn('id', $orderPayments->pluck('order_id'))
                        ->whereIn('ecommerce_orders' . '.brand', $brand)
                        ->orderBy('created_on')
                        ->chunk(
                            100,
                            function ($orders) use (
                                $orderPaymentDetails,
                                $orderPayments,
                                $rowDataTemplate,
                                &$rows,
                                &$alreadyCalculatedPaymentIds,
                                $paymentMethods
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
                                        ->keyBy('id');
                                $users =
                                    $this->userRepository->continueOrNewQuery()
                                        ->whereIn('id', $orders->pluck('user_id'))
                                        ->get()
                                        ->keyBy('id');

                                $shippingAddresses =
                                    $this->addressRepository->query()
                                        ->whereIn(
                                            'id',
                                            $orders->pluck('shipping_address_id')
                                        )
                                        ->orWhereIn('id', $paymentMethods->pluck('billing_address_id'))
                                        ->get()
                                        ->keyBy('id');

                                foreach ($orders as $order) {
                                    $dataRow = $rowDataTemplate;
                                    $items = [];
                                    if ($orderItems->has($order->id)) {
                                        $items = $orderItems[$order->id];

                                    }
                                    $payment = $orderPaymentDetails[$orderPayments[$order->id]->payment_id];
                                    $alreadyCalculatedPaymentIds[$payment->id] = $payment->id;
                                    $totalPaidThisOrder = $payment->paid;
                                    $totalRefunded = $payment->refunded;
                                    foreach ($items as $item) {
                                        $dataRow['products'] .= $item->product['name'] .
                                            ' - ' .
                                            $item->quantity .
                                            '<br>';
                                    }
                                    if ($users->has("$order->user_id")) {
                                        $dataRow['email'] = $users[$order->user_id]['email'];
                                    } elseif ($customers->has("$order->customer_id")) {
                                        $dataRow['email'] = $customers[$order->customer_id]['email'];
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
                                        if ($order->paid < $order->due && $order->due > 0 && $totalRefunded == 0) {
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

                                    if (empty($region) && $dataRow['tax paid'] > 0) {
                                        if (!is_null(
                                            $paymentMethods[$payment->payment_method_id]->billing_address_id
                                        )) {
                                            $region =
                                                $shippingAddresses[$paymentMethods[$payment->payment_method_id]->billing_address_id]['state'];

                                            if (empty($dataRow['address'])) {
                                                $dataRow['address'] = 'Canada';
                                            }
                                            $dataRow['address'] .= ' - ' . $region;
                                        }
                                    }
                                    $rows[] = $dataRow;
                                }
                            }
                        );

                    //renewal statistics
                    $subscriptionRenewalPayments =
                        $this->subscriptionPaymentRepository->query()
                            ->whereIn('payment_id', $allPayments->pluck('id'))
                            ->get();
                    $subscriptionPaymentDetails =
                        $this->paymentRepository->query()
                            ->whereIn('id', $subscriptionRenewalPayments->pluck('payment_id'))
                            ->get()
                            ->keyBy('id');
                    $this->subscriptionRepository->query()
                        ->whereIn('id', $subscriptionRenewalPayments->pluck('subscription_id'))
                        ->whereIn('ecommerce_subscriptions' . '.brand', $brand)
                        ->orderBy('created_on')
                        ->chunk(
                            100,
                            function ($subscriptions) use (
                                &$subscriptionRenewalPayments,
                                &$subscriptionPaymentDetails,
                                &$rows,
                                $rowDataTemplate,
                                &$alreadyCalculatedPaymentIds,
                                $paymentMethods,
                                $brand
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
                                $shippingAddresses =
                                    $this->addressRepository->query()
                                        ->whereIn('id', $paymentMethods->pluck('billing_address_id'))
                                        ->get()
                                        ->keyBy('id');
                                $subscriptionRenewalPayments = $subscriptionRenewalPayments->keyBy('subscription_id');
                                $products =
                                    $this->productRepository->query()
                                        ->select('id', 'name')
                                        ->whereIn('id', $subscriptions->pluck('product_id'))
                                        ->get()
                                        ->keyBy('id');
                                foreach ($subscriptions as $subscription) {
                                    $payment =
                                        $subscriptionPaymentDetails[$subscriptionRenewalPayments[$subscription->id]['payment_id']];
                                    if (isset($alreadyCalculatedPaymentIds[$payment->id])) {
                                        continue;
                                    }

                                    $dataRow = $rowDataTemplate;
                                    $dataRow['total paid'] = $payment->paid;
                                    if ($users->has("$subscription->user_id")) {
                                        $dataRow['email'] = $users[$subscription->user_id]['email'];
                                    } elseif ($customers->has("$subscription->customer_id")) {
                                        $dataRow['email'] = $customers[$subscription->customer_id]['email'] ?? '';
                                    } else {
                                        $dataRow['email'] = 'unknown';
                                    }

                                    if (($subscription->type == Product::TYPE_SUBSCRIPTION) &&
                                        ($subscription->product_id)) {
                                        $dataRow['products'] .= $products[$subscription->product_id]->name;
                                        if ($payment->refunded == 0) {
                                            $dataRow['tax paid'] = $subscription->tax_per_payment;
                                        }
                                    } else {
                                        if ($subscription->order_id) {
                                            $orderForPaymentPlansRenewed =
                                                $this->orderRepository->query()
                                                    ->whereIn('id', [$subscription->order_id])
                                                    ->whereIn('ecommerce_orders' . '.brand', $brand)
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
                                            if (($payment->refunded == 0) &&
                                                ($orderForPaymentPlansRenewed[$subscription->order_id]->due > 0)) {
                                                $dataRow['tax paid'] =
                                                    $orderForPaymentPlansRenewed[$subscription->order_id]->tax *
                                                    $payment->paid /
                                                    $orderForPaymentPlansRenewed[$subscription->order_id]->due;
                                            }
                                        }
                                    }
                                    $dataRow['net paid'] =
                                        $dataRow['total paid'] - $dataRow['tax paid'] - $payment->refunded;

                                    if (empty($dataRow['address']) && $dataRow['tax paid'] > 0) {
                                        if (!is_null(
                                            $paymentMethods[$payment->payment_method_id]->billing_address_id
                                        )) {
                                            $region =
                                                $shippingAddresses[$paymentMethods[$payment->payment_method_id]->billing_address_id]['state'];

                                            if (empty($dataRow['address'])) {
                                                $dataRow['address'] = 'Canada';
                                            }

                                            $dataRow['address'] .= ' - ' . $region;
                                        }
                                    }
                                    $rows[] = $dataRow;
                                }
                            }
                        );
                }
            );

        $rowTotals = [
            'net paid' => array_sum(array_pluck($rows, 'net paid')),
            'shipping paid' => array_sum(array_pluck($rows, 'shipping paid')),
            'finance paid' => array_sum(array_pluck($rows, 'finance paid')),
            'tax paid' => array_sum(array_pluck($rows, 'tax paid')),
            'total paid' => array_sum(array_pluck($rows, 'total paid')),
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