<?php

namespace Railroad\Ecommerce\Commands;

use Carbon\Carbon;
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

        $this->subscriptionRepository        = $subscriptionRepository;
        $this->paymentRepository             = $paymentRepository;
        $this->stripePaymentGateway          = $stripePaymentGateway;
        $this->paypalPaymentGateway          = $payPalPaymentGateway;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('------------------Renewal Due Subscriptions command------------------');
        $dueSubscriptions = $this->subscriptionRepository->query()
            ->select(ConfigService::$tableSubscription . '.*')
            ->join(
                ConfigService::$tableSubscriptionPayment,
                ConfigService::$tableSubscription . '.id',
                '=',
                ConfigService::$tableSubscriptionPayment . '.subscription_id'
            )
            ->join(
                ConfigService::$tablePayment,
                ConfigService::$tableSubscriptionPayment . '.payment_id',
                '=',
                ConfigService::$tablePayment . '.id'
            )
            ->where('paid_until', '<=', Carbon::now()->toDateTimeString())
            ->where('is_active', '=', true)
            ->get()
            ->toArray();

        $this->info('Attempting to renew subscriptions. Count: ' . count($dueSubscriptions));
        $pay = [];

        foreach($dueSubscriptions as $dueSubcription)
        {
            //check for payment plan if the user have already paid all the cycles
            if(($dueSubcription['type'] == config('constants.TYPE_PAYMENT_PLAN')) &&
                ((int)$dueSubcription['total_cycles_paid'] >= (int)$dueSubcription['total_cycles_due']))
            {
                continue;
            }
            if($dueSubcription['payment_method']['method_type'] == PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE)
            {
                $customer = $this->stripePaymentGateway->getCustomer(
                    $dueSubcription['payment_method']['method']['payment_gateway_name'],
                    $dueSubcription['payment_method']['method']['external_customer_id']
                );
                $card     = $this->stripePaymentGateway->getCard(
                    $customer,
                    $dueSubcription['payment_method']['method']['external_id'],
                    $dueSubcription['payment_method']['method']['payment_gateway_name']
                );

                $charge      = $this->stripePaymentGateway->chargeCustomerCard(
                    $dueSubcription['payment_method']['method']['payment_gateway_name'],
                    $dueSubcription['total_price_per_payment'],
                    $dueSubcription['currency'],
                    $card,
                    $customer,
                    ''
                );
                $paymentData = [
                    'paid'              => $charge->amount,
                    'external_provider' => 'stripe',
                    'external_id'       => $charge->id,
                    'status'            => ($charge->status == 'succeeded') ? 1 : 0,
                    'message'           => '',
                    'currency'          => $charge->currency,
                ];
            }
            else if($dueSubcription['payment_method']['method_type'] == PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE)
            {
                $transactionId = $this->payPalPaymentGateway->chargeBillingAgreement(
                    $dueSubcription['payment_method']['method']['payment_gateway_name'],
                    $dueSubcription['total_price_per_payment'],
                    $dueSubcription['currency'],
                    $dueSubcription['payment_method']['method']['external_id'],
                    ''
                );

                $paymentData = [
                    'paid'              => $dueSubcription['total_price_per_payment'],
                    'external_provider' => 'paypal',
                    'external_id'       => $transactionId,
                    'status'            => 1,
                    'message'           => '',
                    'currency'          => $dueSubcription['currency'],
                ];
            }

            //save payment data in DB
            $payment = $this->paymentRepository->create(
                array_merge(
                    $paymentData, [
                        'due'               => $dueSubcription['total_price_per_payment'],
                        'type'              => config('constants.RENEWAL_PAYMENT_TYPE'),
                        'payment_method_id' => $dueSubcription['payment_method']['id'],
                        'created_on'        => Carbon::now()->toDateTimeString()
                    ]
                )
            );

            $subscriptionPayment = $this->subscriptionPaymentRepository->create([
                'subscription_id' => $dueSubcription['id'],
                'payment_id'      => $payment['id'],
                'created_on'      => Carbon::now()->toDateTimeString()
            ]);

            if($dueSubcription['interval_type'] == config('constants.INTERVAL_TYPE_MONTHLY'))
            {
                $nextBillDate = Carbon::now()->addMonths($dueSubcription['interval_count']);
            }
            elseif($dueSubcription['interval_type'] == config('constants.INTERVAL_TYPE_YEARLY'))
            {
                $nextBillDate = Carbon::now()->addYears($dueSubcription['interval_count']);
            }
            elseif($dueSubcription['interval_type'] == config('constants.INTERVAL_TYPE_DAILY'))
            {
                $nextBillDate = Carbon::now()->addDays($dueSubcription['interval_count']);
            }

            $this->subscriptionRepository->update(
                [
                    'total_cycles_paid' => $dueSubcription['total_cycles_paid'] + 1,
                    'paid_until'        => $nextBillDate->toDateTimeString(),
                    'updated_on'        => Carbon::now()->toDateTimeString()
                ]);
        }

        $this->info('-----------------End Renewal Due Subscriptions command-----------------------');
    }
}