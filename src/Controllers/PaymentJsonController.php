<?php

namespace Railroad\Ecommerce\Controllers;


use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Requests\PaymentCreateRequest;
use Railroad\Ecommerce\Responses\JsonResponse;
use Railroad\Ecommerce\Services\PaymentService;

class PaymentJsonController extends Controller
{
    /**
     * @var PaymentService
     */
    private $paymentService;

    /**
     * PaymentJsonController constructor.
     * @param PaymentService $paymentService
     */
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /** Call the method that save a new payment and create the links with subscription or order if it's necessary.
     * Return a JsonResponse with the new created payment record, in JSON format
     * @param PaymentCreateRequest $request
     * @return JsonResponse
     */
    public function store(PaymentCreateRequest $request)
    {
        $payment = $this->paymentService->store(
            $request->get('due'),
            $request->get('paid'),
            $request->get('refunded'),
            $request->get('type'),
            $request->get('payment_method_id'),
            $request->get('currency'),
            $request->get('order_id'),
            $request->get('subscription_id')
        );

        return new JsonResponse($payment, 200);
    }
}