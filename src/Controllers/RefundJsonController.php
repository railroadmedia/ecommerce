<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotAllowedException;
use Railroad\Ecommerce\Factories\GatewayFactory;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\RefundRepository;
use Railroad\Ecommerce\Requests\RefundCreateRequest;
use Railroad\Ecommerce\Responses\JsonResponse;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Permissions\Services\PermissionService;

class RefundJsonController extends Controller
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
     * @var \Railroad\Ecommerce\Factories\GatewayFactory
     */
    private $gatewayFactory;

    /**
     * @var StripePaymentGateway
     */
    private $stripePaymentGateway;

    /**
     * @var PayPalPaymentGateway
     */
    private $payPalPaymentGateway;

    /**
     * RefundJsonController constructor.
     *
     * @param \Railroad\Ecommerce\Repositories\RefundRepository  $refundRepository
     * @param \Railroad\Ecommerce\Repositories\PaymentRepository $paymentRepository
     * @param \Railroad\Permissions\Services\PermissionService   $permissionService
     * @param \Railroad\Ecommerce\Factories\GatewayFactory       $gatewayFactory
     */
    public function __construct(
        RefundRepository $refundRepository,
        PaymentRepository $paymentRepository,
        PermissionService $permissionService,
        GatewayFactory $gatewayFactory,
        StripePaymentGateway $stripePaymentGateway,
        PayPalPaymentGateway $payPalPaymentGateway
    ) {
        $this->refundRepository     = $refundRepository;
        $this->paymentRepository    = $paymentRepository;
        $this->permissionService    = $permissionService;
        $this->gatewayFactory       = $gatewayFactory;
        $this->stripePaymentGateway = $stripePaymentGateway;
        $this->payPalPaymentGateway = $payPalPaymentGateway;
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

        // if the logged in user it's not admin => can refund only own charge
        throw_if(((!$this->permissionService->is(auth()->id(), 'admin')) && (auth()->id() != $payment['user']['user_id'])),
            new NotAllowedException('This action is unauthorized.')
        );

        if($payment['payment_method']['method_type'] == PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE)
        {
            $refundExternalId = $this->stripePaymentGateway->refund(
                $request->get('gateway-name'),
                $request->get('refund_amount'),
                $payment['external_id'],
                $request->get('note')
            );
        }
        else if($payment['payment_method']['method_type'] == PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE)
        {
            $refundExternalId = $this->payPalPaymentGateway->refund(
                $request->get('refund_amount'),
                $payment['currency'],
                $payment['external_id'],
                $request->get('gateway-name'),
                $request->get('note')
            );
        }

        $refund = $this->refundRepository->create(
            [
                'payment_id'        => $payment['id'],
                'payment_amount'    => $payment['due'],
                'refunded_amount'   => $request->get('refund_amount'),
                'note'              => $request->get('note'),
                'external_provider' => $payment['external_provider'],
                'external_id'       => $refundExternalId,
                'created_on'        => Carbon::now()->toDateTimeString()
            ]
        );

        return new JsonResponse($refund, 200);
    }
}