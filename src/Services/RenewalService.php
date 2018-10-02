<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Exception;
use Railroad\Ecommerce\Events\SubscriptionEvent;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Repositories\OrderItemRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;

class RenewalService
{
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

    /**
     * RenewalService constructor.
     *
     * @param SubscriptionRepository $subscriptionRepository
     * @param PaymentRepository $paymentRepository
     * @param StripePaymentGateway $stripePaymentGateway
     * @param PayPalPaymentGateway $payPalPaymentGateway
     * @param SubscriptionPaymentRepository $subscriptionPaymentRepository
     * @param OrderItemRepository $orderItemRepository
     * @param UserProductService $userProductService
     */
    public function __construct(
        SubscriptionRepository $subscriptionRepository,
        PaymentRepository $paymentRepository,
        StripePaymentGateway $stripePaymentGateway,
        PayPalPaymentGateway $payPalPaymentGateway,
        SubscriptionPaymentRepository $subscriptionPaymentRepository,
        OrderItemRepository $orderItemRepository,
        UserProductService $userProductService
    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->paymentRepository = $paymentRepository;
        $this->stripePaymentGateway = $stripePaymentGateway;
        $this->paypalPaymentGateway = $payPalPaymentGateway;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->userProductService = $userProductService;
    }

    /**
     * @param $subscriptionId
     * @return mixed
     * @throws Exception
     */
    public function renew($subscriptionId)
    {
        $dueSubscription = $this->subscriptionRepository->read($subscriptionId);

        //check for payment plan if the user have already paid all the cycles
        if (($dueSubscription['type'] == ConfigService::$paymentPlanType) &&
            ((int)$dueSubscription['total_cycles_paid'] >= (int)$dueSubscription['total_cycles_due'])) {
            return false;
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

                $paymentException = $exception;
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

                $paymentException = $exception;
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
                    'is_active' => true,
                    'canceled_on' => null,
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

            event(new SubscriptionEvent($dueSubscription['id'], 'deactivated'));

            throw $paymentException;
        }

        return $this->subscriptionRepository->read($subscriptionId);
    }
}