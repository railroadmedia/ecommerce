<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\OrderPayment;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\PaymentTaxes;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\SubscriptionPayment;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Events\PaymentEvent;
use Railroad\Ecommerce\Events\Subscriptions\UserSubscriptionRenewed;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Exceptions\TransactionFailedException;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository;
use Railroad\Ecommerce\Requests\PaymentCreateRequest;
use Railroad\Ecommerce\Requests\PaymentIndexRequest;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\InvoiceService;
use Railroad\Ecommerce\Services\PaymentService;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Ecommerce\Services\TaxService;
use Railroad\Permissions\Exceptions\NotAllowedException;
use Railroad\Permissions\Services\PermissionService;
use Spatie\Fractal\Fractal;
use Throwable;

class PaymentJsonController extends Controller
{
    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var InvoiceService
     */
    private $invoiceService;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var PaymentRepository
     */
    private $paymentRepository;

    private $paymentService;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * @var CurrencyService
     */
    private $currencyService;

    /**
     * @var StripePaymentGateway
     */
    private $stripePaymentGateway;

    /**
     * @var PayPalPaymentGateway
     */
    private $payPalPaymentGateway;

    /**
     * @var TaxService
     */
    private $taxService;

    /**
     * @var UserPaymentMethodsRepository
     */
    private $userPaymentMethodsRepository;

    // subscription interval type
    const INTERVAL_TYPE_DAILY = 'day';
    const INTERVAL_TYPE_MONTHLY = 'month';
    const INTERVAL_TYPE_YEARLY = 'year';

    /**
     * PaymentJsonController constructor.
     *
     * @param CurrencyService $currencyService
     * @param EcommerceEntityManager $entityManager
     * @param InvoiceService $invoiceService
     * @param OrderRepository $orderRepository
     * @param PaymentRepository $paymentRepository
     * @param PaymentService $paymentService
     * @param PayPalPaymentGateway $payPalPaymentGateway
     * @param PermissionService $permissionService
     * @param StripePaymentGateway $stripePaymentGateway
     * @param SubscriptionRepository $subscriptionRepository
     * @param TaxService $taxService
     * @param UserPaymentMethodsRepository $userPaymentMethodsRepository
     */
    public function __construct(
        CurrencyService $currencyService,
        EcommerceEntityManager $entityManager,
        InvoiceService $invoiceService,
        OrderRepository $orderRepository,
        PaymentRepository $paymentRepository,
        PaymentService $paymentService,
        PayPalPaymentGateway $payPalPaymentGateway,
        PermissionService $permissionService,
        StripePaymentGateway $stripePaymentGateway,
        SubscriptionRepository $subscriptionRepository,
        TaxService $taxService,
        UserPaymentMethodsRepository $userPaymentMethodsRepository
    ) {
        $this->currencyService = $currencyService;
        $this->entityManager = $entityManager;
        $this->invoiceService = $invoiceService;
        $this->orderRepository = $orderRepository;
        $this->paymentRepository = $paymentRepository;
        $this->paymentService = $paymentService;
        $this->payPalPaymentGateway = $payPalPaymentGateway;
        $this->permissionService = $permissionService;
        $this->stripePaymentGateway = $stripePaymentGateway;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->taxService = $taxService;
        $this->userPaymentMethodsRepository = $userPaymentMethodsRepository;
    }

    /**
     * @param PaymentIndexRequest $request
     *
     * @return JsonResponse
     *
     * @throws NotAllowedException
     */
    public function index(PaymentIndexRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'list.payment');

        $paymentsAndBuilder = $this->paymentRepository->indexByRequest($request);

        return ResponseService::payment($paymentsAndBuilder->getResults(), $paymentsAndBuilder->getQueryBuilder())
            ->respond(200);
    }

    /**
     * Call the method that save a new payment and create the linksluanhc tho with subscription or order if it's necessary.
     * Return a JsonResponse with the new created payment record, in JSON format
     *
     * @param PaymentCreateRequest $request
     *
     * @return Fractal
     *
     * @throws NotAllowedException
     * @throws Throwable
     */
    public function store(PaymentCreateRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'create.payment');

        $paymentPrice = $request->input('data.attributes.due');

        $gateway = $request->input('data.attributes.payment_gateway');

        $paymentType =
            $request->input('data.relationships.subscription.data.id') ? config('ecommerce.renewal_payment_type') :
                config('ecommerce.order_payment_type');

        // if the currency not exist on the request and the payment it's manual,
        // get the currency with Location package, based on ip address
        $currency =
            $request->input('data.attributes.currency')
            ??
            $this->currencyService->get();

        $exception = null;

        if (!$request->input('data.relationships.paymentMethod.data.id')) {
            // if the logged in user it's not admin => can not pay without payment method
            throw_if(
                !$this->permissionService->can(
                    auth()->id(),
                    'create.manual.payment'
                ),
                new NotAllowedException('This action is unauthorized.')
            );

            $conversionRate = $this->currencyService->getRate($currency);

            $user = $paymentMethod = null;

            $payment = new Payment();

            $payment->setTotalDue($paymentPrice);
            $payment->setTotalPaid($paymentPrice);
            $payment->setTotalRefunded(0);
            $payment->setAttemptNumber(0);
            $payment->setExternalProvider(Payment::EXTERNAL_PROVIDER_MANUAL);
            $payment->setType($paymentType);
            $payment->setConversionRate($conversionRate);
            $payment->setGatewayName($gateway);
            $payment->setStatus(Payment::STATUS_PAID);
            $payment->setCurrency($currency);
            $payment->setCreatedAt(Carbon::now());

            $this->entityManager->persist($payment);
        } else {
            $userPaymentMethod = $this->userPaymentMethodsRepository->getByMethodId(
                $request->input('data.relationships.paymentMethod.data.id')
            );

            /**
             * @var $user User
             */
            $user = $userPaymentMethod->getUser();

            // if the logged in user it's not admin => can pay only with own payment method
            throw_if(
                ((!$this->permissionService->can(
                        auth()->id(),
                        'create.payment.method'
                    )) && (auth()->id() != ($user->getId() ?? 0))),
                new NotAllowedException('This action is unauthorized.')
            );

            /**
             * @var $paymentMethod PaymentMethod
             */
            $paymentMethod = $userPaymentMethod->getPaymentMethod();

            $currency = $currency ?? $paymentMethod->getCurrency();

            $payment = null;

            try {
                $payment = $this->paymentService->chargeUsersExistingPaymentMethod(
                    $paymentMethod->getId(),
                    $currency,
                    $paymentPrice,
                    $user->getId(),
                    $paymentType,
                    false
                );
            } catch (Exception $paymentFailedException) {
                $exception = new TransactionFailedException(
                    $paymentFailedException->getMessage()
                );

                if ($paymentFailedException instanceof PaymentFailedException) {
                    if ($payment = $paymentFailedException->getPayment()) {
                        // store failed payment data
                        $this->entityManager->persist($payment);
                    }
                }
            }
        }

        $subscription = null;

        if ($request->input('data.relationships.subscription.data.id')) {
            $subscriptionId = $request->input(
                'data.relationships.subscription.data.id'
            );

            $subscription = $this->subscriptionRepository->find($subscriptionId);

            $payment->setAttemptNumber($subscription->getRenewalAttempt());

            $subscriptionPayment = new SubscriptionPayment();

            $subscriptionPayment->setSubscription($subscription);
            $subscriptionPayment->setPayment($payment);

            $this->entityManager->persist($subscriptionPayment);

            if ($payment->getTotalPaid() > 0) {
                $subscription->setIsActive(true);
                $subscription->setTotalCyclesPaid($subscription->getTotalCyclesPaid() + 1);
                $subscription->setPaidUntil(
                    $this->calculateNextBillDate(
                        $subscription->getIntervalType(),
                        $subscription->getIntervalCount()
                    )
                );

                $subscription->setRenewalAttempt(0);
                $subscription->setFailedPayment(null);
            } else {
                $subscription->setRenewalAttempt($subscription->getRenewalAttempt() + 1);
                $subscription->setFailedPayment($payment);
            }
        }

        $order = null;

        // Save the link between order and payment and save the paid amount on order row
        if ($request->input('data.relationships.order.data.id')) {
            $oderId = $request->input('data.relationships.order.data.id');

            $order = $this->orderRepository->find($oderId);

            $orderPayment = new OrderPayment();

            $orderPayment->setOrder($order);
            $orderPayment->setPayment($payment);
            $orderPayment->setCreatedAt(Carbon::now());

            $this->entityManager->persist($orderPayment);

            /**
             * @var $paymentPlan Subscription
             */
            $paymentPlan = $this->subscriptionRepository->findOneBy(['order' => $order]);

            if ($paymentPlan) {
                $payment->setAttemptNumber($paymentPlan->getRenewalAttempt());
            }

            if ($payment->getTotalPaid() > 0) {
                /**
                 * @var $orderPayments [] \Railroad\Ecommerce\Entities\OrderPayment
                 */
                $orderPayments = $this->paymentRepository->getOrderPayments($order);

                $basedSumPaid = 0;

                foreach ($orderPayments as $pastOrderPayment) {
                    /**
                     * @var $pastOrderPayment OrderPayment
                     */

                    /**
                     * @var $pastPayment Payment
                     */
                    $pastPayment = $pastOrderPayment->getPayment();

                    $paid = ($pastPayment->getTotalPaid() - ($pastPayment->getTotalRefunded() ?? 0));

                    $basedSumPaid += $paid * $pastPayment->getConversionRate();
                }

                $basedPaid = $payment->getTotalPaid() * $payment->getConversionRate();

                $order->setTotalPaid($basedSumPaid + $basedPaid);

                if ($paymentPlan) {
                    $paymentPlan->setRenewalAttempt(0);
                    $paymentPlan->setIsActive(true);
                    $paymentPlan->setTotalCyclesPaid($paymentPlan->getTotalCyclesPaid() + 1);
                    $paymentPlan->setPaidUntil(
                        $this->calculateNextBillDate(
                            $paymentPlan->getIntervalType(),
                            $paymentPlan->getIntervalCount()
                        )
                    );
                }
            } else {
                if ($paymentPlan) {
                    $paymentPlan->setRenewalAttempt($paymentPlan->getRenewalAttempt() + 1);
                }
            }
        }

        if ($payment->getTotalPaid() > 0) {
            $paymentTaxes = new PaymentTaxes();
            $paymentTaxes->setPayment($payment);

            $country = null;
            $region = null;
            $address = null;

            if ($paymentMethod && $paymentMethod->getBillingAddress()) {
                $country = $paymentMethod->getBillingAddress()->getCountry();
                $region = $paymentMethod->getBillingAddress()->getRegion();
                $address = new Address($country, $region);
            } elseif ($subscription && $subscription->getPaymentMethod() &&
                $subscription->getPaymentMethod()->getBillingAddress()) {
                $country = $subscription->getPaymentMethod()->getBillingAddress()->getCountry();
                $region = $subscription->getPaymentMethod()->getBillingAddress()->getRegion();
                $address = new Address($country, $region);
            } elseif ($order) {
                if ($order->getShippingAddress()) {
                    $country = $order->getShippingAddress()->getCountry();
                    $region = $order->getShippingAddress()->getRegion();
                    $address = new Address($country, $region);
                } elseif ($order->getBillingAddress()) {
                    $country = $order->getBillingAddress()->getCountry();
                    $region = $order->getBillingAddress()->getRegion();
                    $address = new Address($country, $region);
                }
            }

            $paymentTaxes->setCountry($country);
            $paymentTaxes->setRegion($region);

            if ($address) {
                $paymentTaxes->setProductRate(
                    $this->taxService->getProductTaxRate($address)
                );
                $paymentTaxes->setShippingRate(
                    $this->taxService->getShippingTaxRate($address)
                );
            }

            $paymentTaxes->setProductTaxesPaid($request->input('data.attributes.product_tax'));
            $paymentTaxes->setShippingTaxesPaid($request->input('data.attributes.shipping_tax'));

            $this->entityManager->persist($paymentTaxes);
        }

        $this->entityManager->flush();

        if ($exception) {
            throw $exception;
        }

        if (!$user && !is_null($order) && !is_null($order->getUser())) {
            $user = $order->getUser();
        }

        if (!is_null($subscription)) {
            event(new UserSubscriptionRenewed($subscription, $payment));
        } else {
            if ($user) {
                event(new PaymentEvent($payment, $user));
            }
        }

        return ResponseService::payment($payment);
    }

    /**
     * Calculate next bill date for subscription
     *
     * @param string $intervalType
     * @param int $intervalCount
     * @return Carbon
     */
    private function calculateNextBillDate($intervalType, $intervalCount)
    {
        $paidUntil = Carbon::now();

        switch ($intervalType) {
            case self::INTERVAL_TYPE_DAILY:
                $paidUntil->addDays($intervalCount);
                break;
            case self::INTERVAL_TYPE_MONTHLY:
                $paidUntil->addMonths($intervalCount);
                break;
            case self::INTERVAL_TYPE_YEARLY:
                $paidUntil->addYears($intervalCount);
                break;
        }

        return $paidUntil;
    }

    /**
     * Soft delete a payment
     *
     * @param int $paymentId
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function delete($paymentId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'delete.payment');

        $payment = $this->paymentRepository->findOneBy(['id' => $paymentId]);

        throw_if(
            is_null($payment),
            new NotFoundException(
                'Delete failed, payment not found with id: ' . $paymentId
            )
        );

        $payment->setDeletedAt(Carbon::now());

        $this->entityManager->flush();

        return ResponseService::empty(204);
    }

    /**
     * Send a payment invoice email
     *
     * @param int $paymentId
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function sendInvoice($paymentId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'send_payment_invoice');

        $payment = $this->paymentRepository->findOneBy(['id' => $paymentId]);

        throw_if(
            is_null($payment),
            new NotFoundException(
                'Invoice sending failed, payment not found with id: ' . $paymentId
            )
        );

        $order = $payment->getOrder();
        $subscription = $payment->getSubscription();

        if (!empty($order)) {
            $this->invoiceService->sendOrderInvoiceEmail($order, $payment);
        } else {
            if (!empty($subscription)) {
                $this->invoiceService->sendSubscriptionRenewalInvoiceEmail($subscription, $payment);
            } else {
                throw new NotFoundException(
                    'Invoice sending failed, payment with id: ' . $paymentId . ' has no associated order or subscription'
                );
            }
        }

        return ResponseService::empty(204);
    }

    public function testSendInvoice()
    {
        $paymentId = 921381; //sends email to robert@musora.com
        $payment = $this->paymentRepository->findOneBy(['id' => $paymentId]);

        throw_if(
            is_null($payment),
            new NotFoundException(
                'Invoice sending failed, payment not found with id: ' . $paymentId
            )
        );

        $order = $payment->getOrder();
        $subscription = $payment->getSubscription();

        if (!empty($order)) {
            $this->invoiceService->sendOrderInvoiceEmail($order, $payment);
        } else {
            if (!empty($subscription)) {
                $this->invoiceService->sendSubscriptionRenewalInvoiceEmail($subscription, $payment);
            } else {
                throw new NotFoundException(
                    'Invoice sending failed, payment with id: ' . $paymentId . ' has no associated order or subscription'
                );
            }
        }

        return ResponseService::empty(204);
    }
}