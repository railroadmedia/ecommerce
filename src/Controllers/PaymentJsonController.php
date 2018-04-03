<?php

namespace Railroad\Ecommerce\Controllers;


use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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

    public function store(Request $request)
    {
        $payment = $this->paymentService->store(
            $request->get('due'),
            $request->get('paid'),
            $request->get('refunded'),
            $request->get('type'),
            $request->get('external_provider'),
            $request->get('external_id'),
            $request->get('status'),
            $request->get('message'),
            $request->get('payment_method_id'),
            $request->get('order_id'),
            $request->get('subscription_id')
        );


        return new JsonResponse($payment, 200);
    }
}