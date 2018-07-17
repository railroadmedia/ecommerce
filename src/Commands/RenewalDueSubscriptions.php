<?php

namespace Railroad\Ecommerce\Commands;

use Carbon\Carbon;
use Exception;
use Railroad\Ecommerce\Events\SubscriptionEvent;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;

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

    public function __construct(
        SubscriptionRepository $subscriptionRepository,
        PaymentRepository $paymentRepository,
        StripePaymentGateway $stripePaymentGateway,
        PayPalPaymentGateway $payPalPaymentGateway,
        SubscriptionPaymentRepository $subscriptionPaymentRepository
    ) {
        parent::__construct();

        $this->subscriptionRepository = $subscriptionRepository;
        $this->paymentRepository = $paymentRepository;
        $this->stripePaymentGateway = $stripePaymentGateway;
        $this->paypalPaymentGateway = $payPalPaymentGateway;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
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
            $this->subscriptionRepository->query()->select(ConfigService::$tableSubscription . '.*')->where(
                    'paid_until',
                    '<=',
                    Carbon::now()->toDateTimeString()
                )->where(
                    'paid_until',
                    '>=',
                    Carbon::now()->subDays(7)->toDateTimeString()
                )->where('is_active', '=', true)->whereNull('canceled_on')->get()->toArray();

        $this->info('Attempting to renew subscriptions. Count: ' . count($dueSubscriptions));

        foreach ($dueSubscriptions as $dueSubscription) {

            //check for payment plan if the user have already paid all the cycles
            if (($dueSubscription['type'] == ConfigService::$paymentPlanType) &&
                ((int)$dueSubscription['total_cycles_paid'] >= (int)$dueSubscription['total_cycles_due'])) {
                continue;
            }


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
                        'created_on' => Carbon::now()->toDateTimeString(),
                    ]
                )
            );

            $subscriptionPayment = $this->subscriptionPaymentRepository->create(
                [
                    'subscription_id' => $dueSubscription['id'],
                    'payment_id' => $payment['id'],
                    'created_on' => Carbon::now()->toDateTimeString(),
                ]
            );

            if ($dueSubscription['interval_type'] == ConfigService::$intervalTypeMonthly) {
                $nextBillDate = Carbon::now()->addMonths($dueSubscription['interval_count']);
            } elseif ($dueSubscription['interval_type'] == ConfigService::$intervalTypeYearly) {
                $nextBillDate = Carbon::now()->addYears($dueSubscription['interval_count']);
            } elseif ($dueSubscription['interval_type'] == ConfigService::$intervalTypeDaily) {
                $nextBillDate = Carbon::now()->addDays($dueSubscription['interval_count']);
            }

            if ($paymentData['paid'] > 0) {
                $this->subscriptionRepository->update(
                    $dueSubscription['id'],
                    [
                        'total_cycles_paid' => $dueSubscription['total_cycles_paid'] + 1,
                        'paid_until' => $nextBillDate->toDateTimeString(),
                        'updated_on' => Carbon::now()->toDateTimeString(),
                    ]
                );

                event(new SubscriptionEvent($dueSubscription['id'], 'renewed'));
            }
        }

        $this->info('-----------------End Renewal Due Subscriptions command-----------------------');
    }
}