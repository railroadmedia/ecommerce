<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Entities\CreditCard;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\OrderPayment;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\PaypalBillingAgreement;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\SubscriptionPayment;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Exceptions\TransactionFailedException;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\CreditCardRepository;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\PaypalBillingAgreementRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository;
use Railroad\Ecommerce\Requests\PaymentCreateRequest;
use Railroad\Ecommerce\Requests\PaymentIndexRequest;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Exceptions\NotAllowedException;
use Railroad\Permissions\Services\PermissionService;
use Spatie\Fractal\Fractal;
use Throwable;

class PaymentJsonController extends Controller
{
    /**
     * @var CreditCardRepository
     */
    private $creditCardRepository;

    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var PaymentRepository
     */
    private $paymentRepository;

    /**
     * @var PaypalBillingAgreementRepository
     */
    private $paypalBillingAgreementRepository;

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
     * @param CreditCardRepository $creditCardRepository
     * @param CurrencyService $currencyService
     * @param EcommerceEntityManager $entityManager
     * @param OrderRepository $orderRepository
     * @param PaymentRepository $paymentRepository
     * @param PaypalBillingAgreementRepository $paypalBillingAgreementRepository
     * @param PayPalPaymentGateway $payPalPaymentGateway
     * @param PermissionService $permissionService
     * @param StripePaymentGateway $stripePaymentGateway
     * @param SubscriptionRepository $subscriptionRepository
     * @param UserPaymentMethodsRepository $userPaymentMethodsRepository
     */
    public function __construct(
        CreditCardRepository $creditCardRepository,
        CurrencyService $currencyService,
        EcommerceEntityManager $entityManager,
        OrderRepository $orderRepository,
        PaymentRepository $paymentRepository,
        PaypalBillingAgreementRepository $paypalBillingAgreementRepository,
        PayPalPaymentGateway $payPalPaymentGateway,
        PermissionService $permissionService,
        StripePaymentGateway $stripePaymentGateway,
        SubscriptionRepository $subscriptionRepository,
        UserPaymentMethodsRepository $userPaymentMethodsRepository
    ) {
        $this->creditCardRepository = $creditCardRepository;
        $this->currencyService = $currencyService;
        $this->entityManager = $entityManager;
        $this->orderRepository = $orderRepository;
        $this->paymentRepository = $paymentRepository;
        $this->paypalBillingAgreementRepository = $paypalBillingAgreementRepository;
        $this->payPalPaymentGateway = $payPalPaymentGateway;
        $this->permissionService = $permissionService;
        $this->stripePaymentGateway = $stripePaymentGateway;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->userPaymentMethodsRepository = $userPaymentMethodsRepository;
    }

    // todo: refactor database logic to repository
    /**
     * @param PaymentIndexRequest $request
     *
     * @return Fractal
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
         * @var $qb QueryBuilder
         */
        $qb = $this->paymentRepository->createQueryBuilder('p');

        $qb
            ->select(['p', 'pm', 'cc', 'ppba'])
            ->leftJoin('p.paymentMethod', 'pm')
            ->leftJoin('pm.creditCard', 'cc')
            ->leftJoin('pm.paypalBillingAgreement', 'ppba')
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

    // todo: refactor database logic to repository
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

        /**
         * @var $qb QueryBuilder
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

        /**
         * @var $user User
         */
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

        /**
         * @var $paymentMethod PaymentMethod
         */
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
                ->setGatewayName($paymentMethod->getMethod()->getPaymentGatewayName())
                ->setStatus(true)
                ->setCurrency($currency)
                ->setCreatedAt(Carbon::now());

        } else if ($paymentMethod->getMethodType() == PaymentMethod::TYPE_CREDIT_CARD) {
            try {

                /**
                 * @var $method CreditCard
                 */
                $method = $paymentMethod->getMethod();

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
                    ->setGatewayName($paymentMethod->getMethod()->getPaymentGatewayName())
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
                    ->setGatewayName($paymentMethod->getMethod()->getPaymentGatewayName())
                    ->setExternalId($charge->id ?? null)
                    ->setStatus('failed')
                    ->setMessage($paymentFailedException->getMessage())
                    ->setCurrency($currency);

            }
        } else if ($paymentMethod->getMethodType() == PaymentMethod::TYPE_PAYPAL) {
            try {

                /**
                 * @var $method PaypalBillingAgreement
                 */
                $method = $paymentMethod->getMethod();

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
                    ->setGatewayName($paymentMethod->getMethod()->getPaymentGatewayName())
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
                    ->setGatewayName($paymentMethod->getMethod()->getPaymentGatewayName())
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
             * @var $subscription Subscription
             */
            $subscription = $this->subscriptionRepository
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
             * @var $order Order
             */
            $order = $this->orderRepository->find($oderId);

            /**
             * @var $orderPayments[] \Railroad\Ecommerce\Entities\OrderPayment
             */
            $orderPayments = $this->paymentRepository
                                ->getOrderPayments($order);

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