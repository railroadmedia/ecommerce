<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Factories\GatewayFactory;
use Railroad\Ecommerce\Repositories\CustomerPaymentMethodsRepository;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository;
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
     * @var PaymentMethodRepository
     */
    private $paymentMethodRepository;

    /**
     * @var UserPaymentMethodsRepository
     */
    private $userPaymentMethodRepository;

    /**
     * @var CustomerPaymentMethodsRepository
     */
    private $customerPaymentMethodRepository;

    /**
     * @var GatewayFactory
     */
    private $gatewayFactory;

    /**
     * PaymentMethodJsonController constructor.
     *
     * @param PaymentMethodService $paymentMethodService
     */
    public function __construct(
        PaymentMethodService $paymentMethodService,
        PaymentMethodRepository $paymentMethodRepository,
        GatewayFactory $gatewayFactory,
        UserPaymentMethodsRepository $userPaymentMethodsRepository,
        CustomerPaymentMethodsRepository $customerPaymentMethodsRepository
    ) {
        $this->paymentMethodService            = $paymentMethodService;
        $this->paymentMethodRepository         = $paymentMethodRepository;
        $this->gatewayFactory                  = $gatewayFactory;
        $this->userPaymentMethodRepository     = $userPaymentMethodsRepository;
        $this->customerPaymentMethodRepository = $customerPaymentMethodsRepository;
    }

    /** Call the service method to create a new payment method based on request parameters.
     * Return - NotFoundException if the request method type parameter it's not defined (paypal or credit card)
     *        - JsonResponse with the new created payment method
     *
     * @param PaymentMethodCreateRequest $request
     * @return JsonResponse|NotFoundException
     */
    public function store(PaymentMethodCreateRequest $request)
    {
        $gateway = $this->gatewayFactory->create($request->get('method_type'));

        $data = $gateway->saveExternalData(
            [
                'paymentGateway'        => $request->get('payment_gateway'),
                'company_name'          => $request->get('company_name'),
                'creditCardYear'        => $request->get('card_year'),
                'creditCardMonth'       => $request->get('card_month'),
                'fingerprint'           => $request->get('card_fingerprint'),
                'last4'                 => $request->get('card_number_last_four_digits'),
                'cardholder'            => $request->get('cardholder_name'),
                'expressCheckoutToken'  => $request->get('express_checkout_token'),
                'address_id'            => $request->get('address_id'),
                'userId'                => $request->get('user_id'),
                'customerId'            => $request->get('customer_id'),
                'stripeUserMapping'     => [],
                'stripeCustomerMapping' => []
            ]);

        //if the store method response it's null the method_type not exist; we throw the proper exception
        throw_if(
            (!$data['status']),
            new NotFoundException('Creation failed, method type(' . $request->get('method_type') . ') not allowed or incorrect data. ')
        );

        $paymentMethod = $this->paymentMethodRepository->create(
            array_merge(
                $request->only([
                    'method_type',
                    'currency',
                ]),
                [
                    'method_id'  => $data['id'],
                    'created_on' => Carbon::now()->toDateTimeString(),
                ])
        );

        if($request->filled('user_id'))
        {
            $this->userPaymentMethodRepository->create([
                'user_id'           => $request->get('user_id'),
                'payment_method_id' => $paymentMethod['id'],
                'created_on'        => Carbon::now()->toDateTimeString()
            ]);
        }

        if($request->filled('customer_id'))
        {
            $this->customerPaymentMethodRepository->create([
                'customer_id'       => $request->get('customer_id'),
                'payment_method_id' => $paymentMethod['id'],
                'created_on'        => Carbon::now()->toDateTimeString()
            ]);
        }

        return new JsonResponse($paymentMethod, 200);
    }

    /** Update a payment method based on request data and payment method id.
     * Return - NotFoundException if the payment method doesn't exist or the user have not rights to access it
     *        - JsonResponse with the updated payment method
     *
     * @param PaymentMethodUpdateRequest $request
     * @param integer                    $paymentMethodId
     * @return JsonResponse|NotFoundException
     */
    public function update(PaymentMethodUpdateRequest $request, $paymentMethodId)
    {
        //update payment method with the data sent on the request
        $paymentMethod = $this->paymentMethodService->update(
            $paymentMethodId,
            $request->only(
                [
                    'update_method',
                    'method_type',
                    'card_year',
                    'card_month',
                    'card_fingerprint',
                    'card_number_last_four_digits',
                    'cardholder_name',
                    'company_name',
                    'express_checkout_token',
                    'address_id',
                    'payment_gateway',
                    'user_id',
                    'customer_id'
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
     *
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