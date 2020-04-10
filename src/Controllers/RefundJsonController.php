<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\OrderPayment;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Refund;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Events\RefundEvent;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\OrderPaymentRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository;
use Railroad\Ecommerce\Requests\RefundCreateRequest;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Services\PermissionService;
use Spatie\Fractal\Fractal;
use Throwable;

class RefundJsonController extends Controller
{
    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var OrderPaymentRepository
     */
    private $orderPaymentRepository;

    /**
     * @var PaymentRepository
     */
    private $paymentRepository;

    /**
     * @var PayPalPaymentGateway
     */
    private $payPalPaymentGateway;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var StripePaymentGateway
     */
    private $stripePaymentGateway;

    /**
     * @var UserPaymentMethodsRepository
     */
    private $userPaymentMethodsRepository;

    /**
     * RefundJsonController constructor.
     *
     * @param EcommerceEntityManager $entityManager
     * @param OrderPaymentRepository $orderPaymentRepository
     * @param PaymentRepository $paymentRepository
     * @param PayPalPaymentGateway $payPalPaymentGateway
     * @param PermissionService $permissionService
     * @param StripePaymentGateway $stripePaymentGateway
     * @param UserPaymentMethodsRepository $userPaymentMethodsRepository
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        OrderPaymentRepository $orderPaymentRepository,
        PaymentRepository $paymentRepository,
        PayPalPaymentGateway $payPalPaymentGateway,
        PermissionService $permissionService,
        StripePaymentGateway $stripePaymentGateway,
        UserPaymentMethodsRepository $userPaymentMethodsRepository
    )
    {
        $this->entityManager = $entityManager;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->paymentRepository = $paymentRepository;
        $this->payPalPaymentGateway = $payPalPaymentGateway;
        $this->permissionService = $permissionService;
        $this->stripePaymentGateway = $stripePaymentGateway;
        $this->userPaymentMethodsRepository = $userPaymentMethodsRepository;
    }

    /**
     * Call the refund method from the external payment helper and the method that save the refund in the database.
     * Return the new created refund in JSON format
     *
     * @param RefundCreateRequest $request
     *
     * @return Fractal
     *
     * @throws Throwable
     * @throws Throwable
     */
    public function store(RefundCreateRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'store.refund');

        $paymentId = $request->input('data.relationships.payment.data.id');

        $payment = $this->paymentRepository->getPaymentAndPaymentMethod($paymentId);

        /**
         * @var $paymentMethod PaymentMethod
         */
        $paymentMethod = $payment->getPaymentMethod();

        $refundExternalId = null;

        if (!empty($paymentMethod)) {
            if ($paymentMethod->getMethodType() == PaymentMethod::TYPE_CREDIT_CARD) {
                $refundExternalId = $this->stripePaymentGateway->refund(
                    $request->input('data.attributes.gateway_name'),
                    $request->input('data.attributes.refund_amount'),
                    $payment->getExternalId(),
                    $request->input('data.attributes.note')
                );
            } elseif ($paymentMethod->getMethodType() == PaymentMethod::TYPE_PAYPAL) {
                $refundExternalId = $this->payPalPaymentGateway->refund(
                    $request->input('data.attributes.refund_amount'),
                    $payment->getCurrency(),
                    $payment->getExternalId(),
                    $request->input('data.attributes.gateway_name'),
                    $request->input('data.attributes.note')
                );
            }
        }

        $refund = new Refund();
        $refund->setPayment($payment);
        $refund->setPaymentAmount($payment->getTotalDue());
        $refund->setRefundedAmount($request->input('data.attributes.refund_amount'));
        $refund->setExternalId($refundExternalId);
        $refund->setExternalProvider($payment->getExternalProvider());
        $refund->setCreatedAt(Carbon::now());
        $refund->setNote($request->input('data.attributes.note'));

        $this->entityManager->persist($refund);

        $payment->setTotalRefunded(
            $payment->getTotalRefunded() + $refund->getRefundedAmount()
        );

        // cancel shipping fulfillment

        $orderPayments = $this->orderPaymentRepository->getByPayment($payment);

        $distinctOrders = [];

        /**
         * @var $orderPayment OrderPayment
         */
        foreach ($orderPayments as $orderPayment) {
            /**
             * @var $order Order
             */
            $order = $orderPayment->getOrder();
            $distinctOrders[$order->getId()] = $order;
        }

        $this->entityManager->flush();

        if (!empty($paymentMethod)) {
            $userPaymentMethod = $this->userPaymentMethodsRepository->findOneBy(['paymentMethod' => $paymentMethod]);

            /** @var $user User */
            if (!empty($userPaymentMethod) && !empty($userPaymentMethod->getUser())) {
                $user = $userPaymentMethod->getUser();

                event(new RefundEvent($refund, $user));
            }
        }

        return ResponseService::refund($refund);
    }
}