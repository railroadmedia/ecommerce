<?php

namespace Railroad\Ecommerce\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Railroad\Ecommerce\Events\SubscriptionEvent;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Repositories\OrderItemRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\UserProductService;

class RenewalDueSubscriptions extends \Illuminate\Console\Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'renewalDueSubscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Renewal of due subscriptions.';

    /**
     * @var \Railroad\Ecommerce\Repositories\SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * @var \Railroad\Ecommerce\Gateways\StripePaymentGateway
     */
    private $stripePaymentGateway;

    /**
     * @var \Railroad\Ecommerce\Gateways\PayPalPaymentGateway
     */
    private $paypalPaymentGateway;

    /**
     * @var \Railroad\Ecommerce\Repositories\PaymentRepository
     */
    private $paymentRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository
     */
    private $subscriptionPaymentRepository;

    /**
     * @var OrderItemRepository
     */
    private $orderItemRepository;

    /**
     * @var UserProductService
     */
    private $userProductService;

    public function __construct(
        SubscriptionRepository $subscriptionRepository,
        PaymentRepository $paymentRepository,
        StripePaymentGateway $stripePaymentGateway,
        PayPalPaymentGateway $payPalPaymentGateway,
        SubscriptionPaymentRepository $subscriptionPaymentRepository,
        OrderItemRepository $orderItemRepository,
        UserProductService $userProductService
    ) {
        parent::__construct();

        $this->subscriptionRepository = $subscriptionRepository;
        $this->paymentRepository = $paymentRepository;
        $this->stripePaymentGateway = $stripePaymentGateway;
        $this->paypalPaymentGateway = $payPalPaymentGateway;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->userProductService = $userProductService;

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Railroad\Ecommerce\Exceptions\PaymentFailedException
     */
    public function handle()
    {
        $this->info('------------------Renewal Due Subscriptions command------------------');

        $dueSubscriptions =
            $this->subscriptionRepository->query()
                ->select(ConfigService::$tableSubscription . '.*')
                ->where('brand', ConfigService::$brand)
                ->where(
                    'paid_until',
                    '<',
                    Carbon::now()
                        ->toDateTimeString()
                )
                ->where(
                    'paid_until',
                    '>=',
                    Carbon::now()
                        ->subMonths(1)
                        ->toDateTimeString()
                )
                ->where('is_active', '=', true)
                ->whereNull('canceled_on')
                ->where(
                    function ($query) {
                        /** @var $query \Eloquent */
                        $query->whereNull(
                            'total_cycles_due'
                        )
                            ->orWhere(
                                'total_cycles_due',
                                0
                            )
                            ->orWhere('total_cycles_paid', '<', DB::raw('`total_cycles_due`'));
                    }
                )
                ->orderBy('start_date')
                ->get()
                ->toArray();

        $this->info('Attempting to renew subscriptions. Count: ' . count($dueSubscriptions));

        foreach ($dueSubscriptions as $dueSubscription) {
            if ($dueSubscription['payment_method']['method_type'] ==
                PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE) {

                try {
                    $customer = $this->stripePaymentGateway->getCustomer(
                        $dueSubscription['payment_method']['method']['payment_gateway_name'],
                        $dueSubscription['payment_method']['method']['external_customer_id']
                    );

                    $card = $this->stripePaymentGateway->getCard(
                        $customer,
                        $dueSubscription['payment_method']['method']['external_id'],
                        $dueSubscription['payment_method']['method']['payment_gateway_name']
                    );

                    $charge = $this->stripePaymentGateway->chargeCustomerCard(
                        $dueSubscription['payment_method']['method']['payment_gateway_name'],
                        $dueSubscription['total_price_per_payment'],
                        $dueSubscription['currency'],
                        $card,
                        $customer,
                        ''
                    );

                    $paymentData = [
                        'paid' => $dueSubscription['total_price_per_payment'],
                        'external_provider' => 'stripe',
                        'external_id' => $charge->id,
                        'status' => 'succeeded',
                        'message' => '',
                        'currency' => $dueSubscription['currency'],
                    ];

                } catch (Exception $exception) {
                    $paymentData = [
                        'paid' => 0,
                        'external_provider' => 'stripe',
                        'external_id' => $charge->id ?? null,
                        'status' => 'failed',
                        'message' => $exception->getMessage(),
                        'currency' => $dueSubscription['currency'],
                    ];
                }

            } elseif ($dueSubscription['payment_method']['method_type'] ==
                PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE) {

                try {
                    $transactionId = $this->paypalPaymentGateway->chargeBillingAgreement(
                        $dueSubscription['payment_method']['method']['payment_gateway_name'],
                        $dueSubscription['total_price_per_payment'],
                        $dueSubscription['currency'],
                        $dueSubscription['payment_method']['method']['external_id'],
                        ''
                    );

                    $paymentData = [
                        'paid' => $dueSubscription['total_price_per_payment'],
                        'external_provider' => 'paypal',
                        'external_id' => $transactionId,
                        'status' => 'succeeded',
                        'message' => '',
                        'currency' => $dueSubscription['currency'],
                    ];

                } catch (Exception $exception) {
                    $paymentData = [
                        'paid' => 0,
                        'external_provider' => 'paypal',
                        'external_id' => $transactionId ?? null,
                        'status' => 'failed',
                        'message' => $exception->getMessage(),
                        'currency' => $dueSubscription['currency'],
                    ];
                }
            }

            //save payment data in DB
            $payment = $this->paymentRepository->create(
                array_merge(
                    $paymentData,
                    [
                        'due' => $dueSubscription['total_price_per_payment'],
                        'type' => ConfigService::$renewalPaymentType,
                        'payment_method_id' => $dueSubscription['payment_method']['id'],
                        'created_on' => Carbon::now()
                            ->toDateTimeString(),
                    ]
                )
            );

            $subscriptionPayment = $this->subscriptionPaymentRepository->create(
                [
                    'subscription_id' => $dueSubscription['id'],
                    'payment_id' => $payment['id'],
                    'created_on' => Carbon::now()
                        ->toDateTimeString(),
                ]
            );

            if ($dueSubscription['interval_type'] == ConfigService::$intervalTypeMonthly) {
                $nextBillDate =
                    Carbon::now()
                        ->addMonths($dueSubscription['interval_count'])
                        ->startOfDay();
            } elseif ($dueSubscription['interval_type'] == ConfigService::$intervalTypeYearly) {
                $nextBillDate =
                    Carbon::now()
                        ->addYears($dueSubscription['interval_count'])
                        ->startOfDay();
            } elseif ($dueSubscription['interval_type'] == ConfigService::$intervalTypeDaily) {
                $nextBillDate =
                    Carbon::now()
                        ->addDays($dueSubscription['interval_count'])
                        ->startOfDay();
            }

            $subscriptionProducts = [];

            //subscription products
            if ($dueSubscription['user_id']) {
                if ($dueSubscription['product_id']) {
                    $subscriptionProducts[] = $dueSubscription['product_id'];
                } elseif ($dueSubscription['order_id']) {
                    $products =
                        $this->orderItemRepository->query()
                            ->where('order_id', $dueSubscription['order_id'])
                            ->get();
                    $subscriptionProducts[] = $products->pluck('id');
                }
            }

            if ($paymentData['paid'] > 0) {
                $this->subscriptionRepository->update(
                    $dueSubscription['id'],
                    [
                        'total_cycles_paid' => $dueSubscription['total_cycles_paid'] + 1,
                        'paid_until' => $nextBillDate->toDateTimeString(),
                        'updated_on' => Carbon::now()
                            ->toDateTimeString(),
                    ]
                );

                foreach ($subscriptionProducts as $product) {
                    $userProduct = $this->userProductService->getUserProductData(
                        $dueSubscription['user_id'],
                        $product
                    );
                    if (!$userProduct) {
                        $this->userProductService->saveUserProduct(
                            $dueSubscription['user_id'],
                            $product,
                            1,
                            $nextBillDate->toDateTimeString()
                        );
                    } else {
                        $this->userProductService->updateUserProduct(
                            $userProduct['id'],
                            $userProduct['quantity'],
                            $nextBillDate->toDateTimeString()
                        );
                    }
                }

                event(new SubscriptionEvent($dueSubscription['id'], 'renewed'));
            } else {
                $this->subscriptionRepository->update(
                    $dueSubscription['id'],
                    [
                        'is_active' => false,
                        'updated_on' => Carbon::now()
                            ->toDateTimeString(),
                    ]
                );

                foreach ($subscriptionProducts as $product) {
                    $userProduct = $this->userProductService->getUserProductData(
                        $dueSubscription['user_id'],
                        $product
                    );

                    if ($userProduct['quantity'] == 1) {
                        $this->userProductService->deleteUserProduct($userProduct['id']);
                    } else {
                        $this->userProductService->updateUserProduct(
                            $userProduct['id'],
                            $userProduct['quantity'] - 1,
                            $userProduct['expiration_date']
                        );
                    }
                }

                event(new SubscriptionEvent($dueSubscription['id'], 'deactivated'));
            }
        }

        $this->info('-----------------End Renewal Due Subscriptions command-----------------------');
    }
}