<?php

namespace Railroad\Ecommerce\Controllers;


use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Requests\PaymentMethodCreateRequest;
use Railroad\Ecommerce\Requests\PaymentMethodDeleteRequest;
use Railroad\Ecommerce\Requests\PaymentMethodUpdateRequest;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Responses\JsonResponse;


class PaymentMethodJsonController extends Controller
{
    /**
     * @var PaymentMethodService
     */
    private $paymentMethodService;

    /**
     * PaymentMethodJsonController constructor.
     * @param PaymentMethodService $paymentMethodService
     */
    public function __construct(PaymentMethodService $paymentMethodService)
    {
        $this->paymentMethodService = $paymentMethodService;
    }

    /** Call the service method to create a new payment method based on request parameters.
     * Return - NotFoundException if the request method type parameter it's not defined (paypal or credit card)
     *        - JsonResponse with the new created payment method
     * @param PaymentMethodCreateRequest $request
     * @return JsonResponse|NotFoundException
     */
    public function store(PaymentMethodCreateRequest $request)
    {
        $paymentMethod = $this->paymentMethodService->store(
            $request->get('method_type'),
            $request->get('card_year'),
            $request->get('card_month'),
            $request->get('card_fingerprint'),
            $request->get('card_number_last_four_digits'),
            $request->get('cardholder_name') ?? '',
            $request->get('company_name'),
            $request->get('external_id'),
            $request->get('agreement_id'),
            $request->get('express_checkout_token'),
            $request->get('address_id'),
            $request->get('user_id'),
            $request->get('customer_id')
        );

        //if the store method response it's null the method_type not exist; we throw the proper exception
        throw_if(
            is_null($paymentMethod),
            new NotFoundException('Creation failed, method type(' . $request->get('method_type') . ') not allowed. ')
        );

        return new JsonResponse($paymentMethod, 200);
    }


    /** Update a payment method based on request data and payment method id.
     * Return - NotFoundException if the payment method doesn't exist or the user have not rights to access it
     *        - JsonResponse with the updated payment method
     * @param PaymentMethodUpdateRequest $request
     * @param integer $paymentMethodId
     * @return JsonResponse|NotFoundException
     */
    public function update(PaymentMethodUpdateRequest $request, $paymentMethodId)
    {
        //update payment method with the data sent on the request
        $paymentMethod = $this->paymentMethodService->update(
            $paymentMethodId,
            array_intersect_key(
                $request->all(),
                [
                    'update_method' => '',
                    'method_type' => '',
                    'card_year' => '',
                    'card_month' => '',
                    'card_fingerprint' => '',
                    'card_number_last_four_digits' => '',
                    'cardholder_name' => '',
                    'company_name' => '',
                    'external_id' => '',
                    'agreement_id' => '',
                    'express_checkout_token' => '',
                    'address_id' => '',
                    'user_id' => '',
                    'customer_id' => ''
                ]
            )
        );

        //if the update method response it's null the product not exist; we throw the proper exception
        throw_if(
            is_null($paymentMethod),
            new NotFoundException('Update failed, payment method not found with id: ' . $paymentMethodId)
        );

        return new JsonResponse($paymentMethod, 201);
    }

    /** Delete a payment method and return a JsonResponse.
     *  Throw  - NotFoundException if the payment method not exist
     * @param integer $paymentMethodId
     * @return JsonResponse
     */
    public function delete($paymentMethodId, PaymentMethodDeleteRequest $request)
    {
        $results = $this->paymentMethodService->delete($paymentMethodId);

        //if the delete method response it's null the payment method not exist; we throw the proper exception
        throw_if(
            is_null($results),
            new NotFoundException('Delete failed, payment method not found with id: ' . $paymentMethodId)
        );

        return new JsonResponse(null, 204);
    }
}