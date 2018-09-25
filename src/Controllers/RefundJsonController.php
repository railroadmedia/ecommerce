<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Exceptions\NotAllowedException;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Repositories\OrderItemFulfillmentRepository;
use Railroad\Ecommerce\Repositories\OrderPaymentRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\RefundRepository;
use Railroad\Ecommerce\Requests\RefundCreateRequest;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Permissions\Services\PermissionService;

class RefundJsonController extends BaseController
{
    /**
     * @var RefundRepository
     */
    private $refundRepository;

    /**
     * @var PaymentRepository
     */
    private $paymentRepository;

    /**
     * @var \Railroad\Permissions\Services\PermissionService
     */
    private $permissionService;

    /**
     * @var StripePaymentGateway
     */
    private $stripePaymentGateway;

    /**
     * @var PayPalPaymentGateway
     */
    private $payPalPaymentGateway;

    /**
     * @var OrderPaymentRepository
     */
    private $orderPaymentRepository;

    /**
     * @var OrderItemFulfillmentRepository
     */
    private $orderItemFulfillmentRepository;

    /**
     * RefundJsonController constructor.
     *
     * @param \Railroad\Ecommerce\Repositories\RefundRepository $refundRepository
     * @param \Railroad\Ecommerce\Repositories\PaymentRepository $paymentRepository
     * @param \Railroad\Permissions\Services\PermissionService $permissionService
     */
    public function __construct(
        RefundRepository $refundRepository,
        PaymentRepository $paymentRepository,
        PermissionService $permissionService,
        StripePaymentGateway $stripePaymentGateway,
        PayPalPaymentGateway $payPalPaymentGateway,
        OrderPaymentRepository $orderPaymentRepository,
        OrderItemFulfillmentRepository $orderItemFulfillmentRepository
    ) {
        parent::__construct();

        $this->refundRepository = $refundRepository;
        $this->paymentRepository = $paymentRepository;
        $this->permissionService = $permissionService;
        $this->stripePaymentGateway = $stripePaymentGateway;
        $this->payPalPaymentGateway = $payPalPaymentGateway;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->orderItemFulfillmentRepository = $orderItemFulfillmentRepository;
    }

    /** Call the refund method from the external payment helper and the method that save the refund in the database.
     * Return the new created refund in JSON format
     *
     * @param RefundCreateRequest $request
     * @return JsonResponse
     */
    public function store(RefundCreateRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'store.refund');

        $payment = $this->paymentRepository->read($request->get('payment_id'));

        if ($payment['payment_method']['method_type'] == PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE) {
            $refundExternalId = $this->stripePaymentGateway->refund(
                $request->get('gateway_name'),
                $request->get('refund_amount'),
                $payment['external_id'],
                $request->get('note')
            );
        } else {
            if ($payment['payment_method']['method_type'] == PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE) {
                $refundExternalId = $this->payPalPaymentGateway->refund(
                    $request->get('refund_amount'),
                    $payment['currency'],
                    $payment['external_id'],
                    $request->get('gateway_name'),
                    $request->get('note')
                );
            }
        }

        $refund = $this->refundRepository->create(
            [
                'payment_id' => $payment['id'],
                'payment_amount' => $payment['due'],
                'refunded_amount' => $request->get('refund_amount'),
                'note' => $request->get('note'),
                'external_provider' => $payment['external_provider'],
                'external_id' => $refundExternalId,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        //update refund column in payment table
        $this->paymentRepository->update(
            $payment['id'],
            [
                'refunded' => $payment['refunded'] + $refund['refunded_amount'],
            ]
        );

        //cancel shipping fulfillment
        $orderPayment =
            $this->orderPaymentRepository->query()
                ->where('payment_id', $payment['id'])
                ->get();
        $this->orderItemFulfillmentRepository->query()
            ->whereIn('order_id', $orderPayment->pluck('order_id'))
            ->where('status', ConfigService::$fulfillmentStatusPending)
            ->whereNull('fulfilled_on')
            ->delete();

        return reply()->json($refund);
    }
}