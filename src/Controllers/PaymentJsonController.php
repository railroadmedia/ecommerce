<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Entities\CreditCard;
use Railroad\Ecommerce\Entities\OrderPayment;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\PaypalBillingAgreement;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\UserPaymentMethods;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\SubscriptionPayment;
use Railroad\Ecommerce\Exceptions\TransactionFailedException;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository;
use Railroad\Ecommerce\Requests\PaymentCreateRequest;
use Railroad\Ecommerce\Requests\PaymentIndexRequest;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Location\Services\LocationService;
use Railroad\Permissions\Exceptions\NotAllowedException;
use Railroad\Permissions\Services\PermissionService;

class PaymentJsonController extends BaseController
{
    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var PaymentRepository
     */
    private $paymentRepository;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var UserPaymentMethodsRepository
     */
    private $userPaymentMethodRepository;

    /**
     * @var CurrencyService
     */
    private $currencyService;

    /**
     * @var \Railroad\Ecommerce\Gateways\StripePaymentGateway
     */
    private $stripePaymentGateway;

    /**
     * @var \Railroad\Ecommerce\Gateways\PayPalPaymentGateway
     */
    private $payPalPaymentGateway;

    // subscription interval type
    const INTERVAL_TYPE_DAILY = 'day';
    const INTERVAL_TYPE_MONTHLY = 'month';
    const INTERVAL_TYPE_YEARLY = 'year';

    /**
     * PaymentJsonController constructor.
     *
     * @param CurrencyService $currencyService
     * @param EcommerceEntityManager $entityManager
     * @param PermissionService $permissionService
     * @param StripePaymentGateway $stripePaymentGateway
     * @param PayPalPaymentGateway $payPalPaymentGateway
     */
    public function __construct(
        CurrencyService $currencyService,
        EcommerceEntityManager $entityManager,
        PermissionService $permissionService,
        StripePaymentGateway $stripePaymentGateway,
        PayPalPaymentGateway $payPalPaymentGateway
    )
    {
        parent::__construct();

        $this->currencyService = $currencyService;
        $this->entityManager = $entityManager;

        $this->userPaymentMethodsRepository = $this->entityManager
                                    ->getRepository(UserPaymentMethods::class);

        $this->paymentRepository = $this->entityManager
                                    ->getRepository(Payment::class);

        $this->permissionService = $permissionService;
        $this->stripePaymentGateway = $stripePaymentGateway;
        $this->payPalPaymentGateway = $payPalPaymentGateway;
    }

    /**
     * @param PaymentIndexRequest $request
     *
     * @throws NotAllowedException
     */
    public function index(PaymentIndexRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'list.payment');

        $alias = 'p';
        $orderBy = $request->get('order_by_column', 'created_at');
        if (
            strpos($orderBy, '_') !== false
            || strpos($orderBy, '-') !== false
        ) {
            $orderBy = camel_case($orderBy);
        }
        $orderBy = $alias . '.' . $orderBy;
        $first = ($request->get('page', 1) - 1) * $request->get('limit', 10);

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->paymentRepository->createQueryBuilder('p');

        $qb
            ->orderBy($orderBy, $request->get('order_by_direction', 'desc'))
            ->setMaxResults($request->get('limit', 100))
            ->setFirstResult($first);

        if (!empty($request->get('order_id'))) {
            $aliasOrderPayment = 'op';
            $qb
                ->join($alias . '.orderPayment', $aliasOrderPayment)
                ->where(
                    $qb->expr()->eq(
                        'IDENTITY(' . $aliasOrderPayment .
                        '.order)', ':orderId'
                    )
                )
                ->setParameter(
                    'orderId',
                    $request->get('order_id')
                );
        }

        if (!empty($request->get('subscription_id'))) {
            $aliasSubscriptionPayment = 'sp';
            $qb
                ->join(
                    $alias . '.subscriptionPayment',
                    $aliasSubscriptionPayment
                )
                ->where(
                    $qb->expr()->eq(
                        'IDENTITY(' . $aliasSubscriptionPayment .
                        '.subscription)', ':subscriptionId'
                    )
                )
                ->setParameter(
                    'subscriptionId',
                    $request->get('subscription_id')
                );
        }

        $payments = $qb->getQuery()->getResult();

        return ResponseService::payment($payments, $qb);
    }

    /**
     * Call the method that save a new payment and create the links with subscription or order if it's necessary.
     * Return a JsonResponse with the new created payment record, in JSON format
     *
     * @param PaymentCreateRequest $request
     *
     * @return JsonResponse
     *
     * @throws NotAllowedException
     * @throws \Throwable
     */
    public function store(PaymentCreateRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'create.payment');

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->userPaymentMethodsRepository->createQueryBuilder('p');

        $userPaymentMethod = $qb
            ->where($qb->expr()->eq('IDENTITY(p.paymentMethod)', ':id'))
            ->setParameter(
                'id',
                $request->input('data.relationships.paymentMethod.data.id')
            )
            ->getQuery()
            ->getOneOrNullResult();

        $user = $userPaymentMethod->getUser();

        // if the logged in user it's not admin => can pay only with own payment method
        throw_if(
            (
                (
                    !$this->permissionService->can(
                        auth()->id(),
                        'create.payment.method'
                    )
                ) &&
                (auth()->id() != ($user->getId() ?? 0))
            ),
            new NotAllowedException('This action is unauthorized.')
        );

        $gateway = $request->input('data.attributes.payment_gateway');

        $paymentMethod = $userPaymentMethod->getPaymentMethod();

        // if the currency not exist on the request and the payment it's manual,
        // get the currency with Location package, based on ip address
        $currency = $request->input('data.attributes.currency')
            ?? $this->currencyService->get()
            ?? $paymentMethod->getCurrency();

        $conversionRate = $this->currencyService->getRate($currency);

        $paymentType = $request->input('data.relationships.subscription.data.id') ?
                            ConfigService::$renewalPaymentType :
                            ConfigService::$orderPaymentType;

        $paymentPrice = $request->input('data.attributes.due');

        $payment = new Payment();

        $exception = null;

        if (is_null($paymentMethod)) {

            $payment
                ->setTotalDue($paymentPrice)
                ->setTotalPaid($paymentPrice)
                ->setExternalProvider(ConfigService::$manualPaymentType)
                ->setStatus(true)
                ->setCurrency($currency)
                ->setCreatedAt(Carbon::now());

        } else if ($paymentMethod->getMethodType() == PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE) {
            try {

                /**
                 * @var $method \Railroad\Ecommerce\Entities\CreditCard
                 */
                $method = $this->entityManager
                                ->getRepository(CreditCard::class)
                                ->find($paymentMethod->getMethodId());

                $customer = $this->stripePaymentGateway->getCustomer(
                    $gateway,
                    $method->getExternalId()
                );

                $card = $this->stripePaymentGateway->getCard(
                    $customer,
                    $method->getExternalId(),
                    $gateway
                );

                $charge = $this->stripePaymentGateway->chargeCustomerCard(
                    $gateway,
                    $paymentPrice,
                    $currency,
                    $card,
                    $customer,
                    ''
                );

                $payment
                    ->setTotalPaid($paymentPrice)
                    ->setExternalProvider('stripe')
                    ->setExternalId($charge->id)
                    ->setStatus(($charge->status == 'succeeded') ? '1' : '0')
                    ->setMessage('')
                    ->setCurrency($charge->currency);

            } catch (Exception $paymentFailedException) {

                $exception = new TransactionFailedException(
                        $paymentFailedException->getMessage()
                    );

                $payment
                    ->setTotalPaid(0)
                    ->setExternalProvider('stripe')
                    ->setExternalId($charge->id ?? null)
                    ->setStatus('failed')
                    ->setMessage($paymentFailedException->getMessage())
                    ->setCurrency($currency);

            }
        } else if ($paymentMethod->getMethodType() == PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE) {
            try {

                /**
                 * @var $method \Railroad\Ecommerce\Entities\PaypalBillingAgreement
                 */
                $method = $this->entityManager
                                ->getRepository(PaypalBillingAgreement::class)
                                ->find($paymentMethod->getMethodId());

                $transactionId = $this->payPalPaymentGateway
                                    ->chargeBillingAgreement(
                                        $gateway,
                                        $paymentPrice,
                                        $currency,
                                        $method->getExternalId(),
                                        ''
                                    );

                $payment
                    ->setTotalPaid($paymentPrice)
                    ->setExternalProvider('paypal')
                    ->setExternalId($transactionId)
                    ->setStatus('1')
                    ->setMessage('')
                    ->setCurrency($currency);

            } catch (Exception $paymentFailedException) {

                $exception = new TransactionFailedException(
                        $paymentFailedException->getMessage()
                    );

                $payment
                    ->setTotalPaid(0)
                    ->setExternalProvider('paypal')
                    ->setExternalId($transactionId ?? null)
                    ->setStatus('failed')
                    ->setMessage($paymentFailedException->getMessage())
                    ->setCurrency($currency);
            }
        }

        $payment
            ->setTotalDue($paymentPrice)
            ->setType($paymentType)
            ->setConversionRate($conversionRate)
            ->setPaymentMethod($paymentMethod)
            ->setCreatedAt(Carbon::now());

        $this->entityManager->persist($payment);

        if ($exception) {

            $this->entityManager->flush();

            throw $exception;
        }

        if ($request->input('data.relationships.subscription.data.id')) {

            $subscriptionId = $request->input(
                'data.relationships.subscription.data.id'
            );

            /**
             * @var $subscription \Railroad\Ecommerce\Entities\Subscription
             */
            $subscription = $this->entityManager
                            ->getRepository(Subscription::class)
                            ->find($subscriptionId);

            $subscription
                ->setTotalCyclesPaid($subscription->getTotalCyclesPaid() + 1)
                ->setPaidUntil(
                    $this->calculateNextBillDate(
                        $subscription->getIntervalType(),
                        $subscription->getIntervalCount()
                    )
                );

            $subscriptionPayment = new SubscriptionPayment();

            $subscriptionPayment
                ->setSubscription($subscription)
                ->setPayment($payment);

            $this->entityManager->persist($subscriptionPayment);
        }

        // Save the link between order and payment and save the paid amount on order row
        if ($request->input('data.relationships.order.data.id')) {

            $oderId = $request->input('data.relationships.order.data.id');

            /**
             * @var $order \Railroad\Ecommerce\Entities\Order
             */
            $order = $this->entityManager
                            ->getRepository(Order::class)
                            ->find($oderId);

            /**
             * @var $orderPayments[] \Railroad\Ecommerce\Entities\OrderPayment
             */
            $orderPayments = $this->paymentRepository
                                ->getOrderPayments($order);

            $basedSumPaid = 0;

            foreach ($orderPayments as $pastOrderPayment) {

                /**
                 * @var $pastOrderPayment \Railroad\Ecommerce\Entities\OrderPayment
                 */

                /**
                 * @var $pastPayment \Railroad\Ecommerce\Entities\Payment
                 */
                $pastPayment = $pastOrderPayment->getPayment();

                $paid = ($pastPayment->getTotalPaid() - ($pastPayment->getTotalRefunded() ?? 0));

                $basedSumPaid += $paid * $pastPayment->getConversionRate();
            }

            $orderPayment = new OrderPayment();

            $orderPayment
                ->setOrder($order)
                ->setPayment($payment)
                ->setCreatedAt(Carbon::now());

            $this->entityManager->persist($orderPayment);

            $basedPaid = $payment->getTotalPaid() *
                            $payment->getConversionRate();

            $order->setTotalPaid($basedSumPaid + $basedPaid);
        }

        $this->entityManager->flush();

        return ResponseService::payment($payment);
    }

    /**
     * Calculate next bill date for subscription
     *
     * @param string $intervalType
     * @param int $intervalCount
     * @return \Carbon\Carbon
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
     */
    public function delete($paymentId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'delete.payment');

        $payment = $this->paymentRepository->find($paymentId);

        throw_if(
            is_null($payment),
            new NotFoundException(
                'Delete failed, payment not found with id: ' . $paymentId
            )
        );

        $payment->setDeletedOn(Carbon::now());

        $this->entityManager->flush();

        return ResponseService::empty(204);
    }
}