<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Railroad\Ecommerce\Entities\OrderItemFulfillment;
use Railroad\Ecommerce\Entities\OrderPayment;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Refund;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Requests\RefundCreateRequest;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Services\PermissionService;

class RefundJsonController extends BaseController
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var PayPalPaymentGateway
     */
    private $payPalPaymentGateway;

    /**
     * @var \Railroad\Permissions\Services\PermissionService
     */
    private $permissionService;

    /**
     * @var StripePaymentGateway
     */
    private $stripePaymentGateway;

    /**
     * RefundJsonController constructor.
     *
     * @param EntityManager $entityManager
     * @param PayPalPaymentGateway $payPalPaymentGateway
     * @param PermissionService $permissionService
     * @param StripePaymentGateway $stripePaymentGateway
     */
    public function __construct(
        EntityManager $entityManager,
        PayPalPaymentGateway $payPalPaymentGateway,
        PermissionService $permissionService,
        StripePaymentGateway $stripePaymentGateway
    ) {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->payPalPaymentGateway = $payPalPaymentGateway;
        $this->permissionService = $permissionService;
        $this->stripePaymentGateway = $stripePaymentGateway;
    }

    /**
     * Call the refund method from the external payment helper and the method that save the refund in the database.
     * Return the new created refund in JSON format
     *
     * @param RefundCreateRequest $request
     * @return JsonResponse
     */
    public function store(RefundCreateRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'store.refund');

        $paymentRepository = $this->entityManager
                                    ->getRepository(Payment::class);

        $paymentId = $request->input('data.relationships.payment.data.id');

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $paymentRepository->createQueryBuilder('p');

        $qb
            ->select(['p', 'pm'])
            ->join('p.paymentMethod', 'pm')
            ->where($qb->expr()->eq('p.id', ':id'))
            ->setParameter('id', $paymentId);

        /**
         * @var $payment Railroad\Ecommerce\Entities\Payment
         */
        $payment = $qb->getQuery()->getOneOrNullResult();

        /**
         * @var $payment Railroad\Ecommerce\Entities\PaymentMethod
         */
        $paymentMethod = $payment->getPaymentMethod();

        if ($paymentMethod->getMethodType() == PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE) {
            $refundExternalId = $this->stripePaymentGateway->refund(
                $request->input('data.attributes.gateway_name'),
                $request->input('data.attributes.refund_amount'),
                $payment->getExternalId(),
                $request->input('data.attributes.note')
            );
        } else if ($paymentMethod->getMethodType() == PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE) {
            $refundExternalId = $this->payPalPaymentGateway->refund(
                $request->input('data.attributes.refund_amount'),
                $payment->getCurrency(),
                $payment->getExternalId(),
                $request->input('data.attributes.gateway_name'),
                $request->input('data.attributes.note')
            );
        }

        $refund = new Refund();

        $refund
            ->setPayment($payment)
            ->setPaymentAmount($payment->getTotalDue())
            ->setRefundedAmount($request->input('data.attributes.refund_amount'))
            ->setNote($request->input('data.attributes.note'))
            ->setExternalId($refundExternalId)
            ->setExternalProvider($payment->getExternalProvider())
            ->setCreatedAt(Carbon::now());

        $this->entityManager->persist($refund);

        $payment->setTotalRefunded(
            $payment->getTotalRefunded() + $refund->getRefundedAmount()
        );

        // cancel shipping fulfillment

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->entityManager
                ->getRepository(OrderPayment::class)
                ->createQueryBuilder('op');

        $qb
            ->select(['op', 'p'])
            ->join('op.payment', 'p')
            ->where($qb->expr()->eq('op.payment', ':payment'))
            ->andWhere($qb->expr()->isNull('p.deletedOn'))
            ->setParameter('payment', $payment);

        $orderPayments = $qb->getQuery()->getResult();

        $distinctOrders = [];

        foreach ($orderPayments as $orderPayment) {
            /**
             * @var $order Railroad\Ecommerce\Entities\Order
             */
            $order = $orderPayment->getOrder();
            $distinctOrders[$order->getId()] = $order;
        }

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->entityManager
                ->getRepository(OrderItemFulfillment::class)
                ->createQueryBuilder('oif');

        $qb
            ->where($qb->expr()->in('oif.order', ':orders'))
            ->andWhere($qb->expr()->eq('oif.status', ':status'))
            ->andWhere($qb->expr()->isNull('oif.fulfilledOn'))
            ->setParameter('orders', array_values($distinctOrders))
            ->setParameter('status', ConfigService::$fulfillmentStatusPending);

        $orderItemFulfillments = $qb->getQuery()->getResult();

        foreach ($orderItemFulfillments as $orderItemFulfillment) {
            $this->entityManager->remove($orderItemFulfillment);
        }

        $this->entityManager->flush();

        return ResponseService::refund($refund);
    }
}