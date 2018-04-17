<?php

namespace Railroad\Ecommerce\Controllers;


use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotAllowedException;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Requests\PaymentGatewayCreateRequest;
use Railroad\Ecommerce\Requests\PaymentGatewayUpdateRequest;
use Railroad\Ecommerce\Responses\JsonResponse;
use Railroad\Ecommerce\Services\PaymentGatewayService;


class PaymentGatewayJsonController extends Controller
{
    /**
     * @var PaymentGatewayService
     */
    private $paymentGatewayService;

    /**
     * PaymentGatewayJsonController constructor.
     * @param PaymentGatewayService $paymentGatewayService
     */
    public function __construct(PaymentGatewayService $paymentGatewayService)
    {
        $this->paymentGatewayService = $paymentGatewayService;
    }


    /** Call the service method to create a new payment gateway based on request parameters.
     * Return a JsonResponse with the new created payment gateway
     * @param  PaymentGatewayCreateRequest $request
     * @return JsonResponse
     */
    public function store(PaymentGatewayCreateRequest $request)
    {
        $paymentGateway = $this->paymentGatewayService->store(
            $request->get('type'),
            $request->get('name'),
            $request->get('config'),
            $request->get('brand')
        );

        return new JsonResponse($paymentGateway, 200);
    }


    /** Update a payment gateway based on request data and payment gateway id.
     * Return - NotFoundException if the payment gateway doesn't exist or the user have not rights to access it
     *        - JsonResponse with the updated payment gateway
     * @param  PaymentGatewayUpdateRequest $request
     * @param integer $paymentGatewayId
     * @return JsonResponse|NotFoundException
     */
    public function update($paymentGatewayId, PaymentGatewayUpdateRequest $request)
    {
        //update payment gateway with the data sent on the request
        $paymentGateway = $this->paymentGatewayService->update(
            $paymentGatewayId,
            array_intersect_key(
                $request->all(),
                [
                    'brand' => '',
                    'type' => '',
                    'name' => '',
                    'config' => ''
                ]
            )
        );

        //if the update method response it's null the payment gateway not exist; we throw the proper exception
        throw_if(
            is_null($paymentGateway),
            new NotFoundException('Update failed, payment gateway not found with id: ' . $paymentGatewayId)
        );

        return new JsonResponse($paymentGateway, 201);
    }

    /** Delete a payment gateway and return a JsonResponse.
     *  Throw  - NotFoundException if the payment gateway not exist
     *         - NotAllowedException if the payment gateway it's in used
     * @param integer $paymentGatewayId
     * @return JsonResponse
     */
    public function delete($paymentGatewayId)
    {
        $results = $this->paymentGatewayService->delete($paymentGatewayId);

        //if the delete method response it's null the payment gateway not exist; we throw the proper exception
        throw_if(
            is_null($results),
            new NotFoundException('Delete failed, payment gateway not found with id: ' . $paymentGatewayId)
        );

        throw_if(
            ($results === 0),
            new NotAllowedException('Delete failed, the payment gateway it\'s in used.')
        );

        return new JsonResponse(null, 204);
    }
}