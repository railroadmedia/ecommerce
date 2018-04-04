<?php


namespace Railroad\Ecommerce\Controllers;


use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Requests\RefundCreateRequest;
use Railroad\Ecommerce\Responses\JsonResponse;
use Railroad\Ecommerce\Services\RefundService;

class RefundJsonController extends Controller
{
    /**
     * @var RefundService
     */
    private $refundService;

    /**
     * RefundJsonController constructor.
     * @param RefundService $refundService
     */
    public function __construct(RefundService $refundService)
    {
        $this->refundService = $refundService;
    }

    /** Call the method that save the refund in the database.
     * Return the new created refund in JSON format
     * @param RefundCreateRequest $request
     * @return JsonResponse
     */
    public function store(RefundCreateRequest $request)
    {
        $refund = $this->refundService->store(
            $request->get('payment_id'),
            $request->get('refund_amount'),
            $request->get('note')
        );

        return new JsonResponse($refund, 200);
    }
}