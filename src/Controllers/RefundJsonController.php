<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Factories\GatewayFactory;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\RefundRepository;
use Railroad\Ecommerce\Requests\RefundCreateRequest;
use Railroad\Ecommerce\Responses\JsonResponse;
use Railroad\Ecommerce\Services\RefundService;
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
        GatewayFactory $gatewayFactory
    ) {
        $this->refundRepository  = $refundRepository;
        $this->paymentRepository = $paymentRepository;
        $this->permissionService = $permissionService;
        $this->gatewayFactory    = $gatewayFactory;
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

        $gateway = $this->gatewayFactory->create($payment['payment_method']['method_type']);

        $refundExternalId = $gateway->refund(
            $payment['payment_method']['method_id'],
            $request->get('refund_amount'),
            $payment['due'],
            $payment['currency'],
            $payment['external_id'],
            $request->get('note')
        );

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