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
use Railroad\Permissions\Services\PermissionService;

class PaymentMethodJsonController extends Controller
{
    /**
     * @var \Railroad\Permissions\Services\PermissionService
     */
    private $permissionService;

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
     * @param \Railroad\Permissions\Services\PermissionService                  $permissionService
     * @param \Railroad\Ecommerce\Repositories\PaymentMethodRepository          $paymentMethodRepository
     * @param \Railroad\Ecommerce\Factories\GatewayFactory                      $gatewayFactory
     * @param \Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository     $userPaymentMethodsRepository
     * @param \Railroad\Ecommerce\Repositories\CustomerPaymentMethodsRepository $customerPaymentMethodsRepository
     */
    public function __construct(
        PermissionService $permissionService,
        PaymentMethodRepository $paymentMethodRepository,
        GatewayFactory $gatewayFactory,
        UserPaymentMethodsRepository $userPaymentMethodsRepository,
        CustomerPaymentMethodsRepository $customerPaymentMethodsRepository
    ) {
        $this->permissionService               = $permissionService;
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

        $data            = $this->saveMethod($request);
        $externalMessage = $data['message'] ?? '';

        //if the store method response it's null the method_type not exist; we throw the proper exception
        throw_if(
            (!$data['status']),
            new NotFoundException('Creation failed, method type(' . $request->get('method_type') . ') not allowed or incorrect data.' . print_r($externalMessage, true))
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

        //assign payment method to user id or customer id
        $this->assignPaymentMethod($request, $paymentMethod);

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
        $this->permissionService->canOrThrow(auth()->id(), 'update.payment.method');

        $paymentMethod = $this->paymentMethodRepository->read($paymentMethodId);

        //if the payment method not exist; we throw the proper exception
        throw_if(
            is_null($paymentMethod),
            new NotFoundException('Update failed, payment method not found with id: ' . $paymentMethodId)
        );

        switch($request->get('update_method'))
        {
            case PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_CREATE_NEW_CREDIT_CARD:
                $methodType = PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE;
                $create     = true;
                break;
            case PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_USE_PAYPAL:
                $methodType = PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE;
                $create     = true;
                break;
            default:
                $methodType = PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE;
                $create     = false;
        }

        if($create)
        {
            $method = $this->saveMethod($request);
            $this->assignPaymentMethod($request, $paymentMethod);
        }
        else
        {
            $this->creditCardRepository->update($paymentMethod['method']['id'],
                [
                    Carbon::create(
                        $request->get('creditCardYear'),
                        $request->get('creditCardMonth'),
                        12,
                        0,
                        0,
                        0
                    ),
                    'updated_on' => Carbon::now()->toDateTimeString()
                ]);
        }

        //update payment method
        $paymentMethodUpdated = $this->paymentMethodRepository->update(
            $paymentMethodId,
            array_merge(
                $request->only(
                    [
                        'currency',
                    ]
                ),
                [
                    'method_type' => $methodType,
                    'method_id'   => $method['id'],
                    'updated_on'  => Carbon::now()->toDateTimeString()
                ])
        );

        return new JsonResponse($paymentMethodUpdated, 201);
    }

    /** Delete a payment method and return a JsonResponse.
     *  Throw  - NotFoundException if the payment method not exist
     *
     * @param integer $paymentMethodId
     * @return JsonResponse
     */
    public function delete($paymentMethodId, PaymentMethodDeleteRequest $request)
    {
        //TODO - user can delete only his payment method
        $this->permissionService->canOrThrow(auth()->id(), 'delete.payment.method');

        $paymentMethod = $this->paymentMethodRepository->read($paymentMethodId);

        //if the delete method response it's null the payment method not exist; we throw the proper exception
        throw_if(
            is_null($paymentMethod),
            new NotFoundException('Delete failed, payment method not found with id: ' . $paymentMethodId)
        );

        $gateway = $this->gatewayFactory->create($paymentMethod['method_type']);
        $gateway->deleteMethod($paymentMethod['method_id']);
        $this->revokePaymentMethod($paymentMethod);
        $results = $this->paymentMethodRepository->destroy($paymentMethodId);

        return new JsonResponse(null, 204);
    }

    /**
     * @param \Railroad\Ecommerce\Requests\PaymentMethodCreateRequest $request
     * @return array|int
     * @throws \Railroad\Ecommerce\Exceptions\NotFoundException
     */
    private function saveMethod($request)
    {
        $gateway = $this->gatewayFactory->create($request->get('method_type'));

        $data = $gateway->saveExternalData(
            [
                'paymentGateway'       => $request->get('payment_gateway'),
                'company_name'         => $request->get('company_name'),
                'creditCardYear'       => $request->get('card_year'),
                'creditCardMonth'      => $request->get('card_month'),
                'fingerprint'          => $request->get('card_fingerprint'),
                'last4'                => $request->get('card_number_last_four_digits'),
                'cardholder'           => $request->get('cardholder_name'),
                'expressCheckoutToken' => $request->get('express_checkout_token'),
                'address_id'           => $request->get('address_id'),
                'userId'               => $request->get('user_id'),
                'customerId'           => $request->get('customer_id')
            ]);

        return $data;
    }

    /**
     * @param \Railroad\Ecommerce\Requests\PaymentMethodCreateRequest $request
     * @param                                                         $paymentMethod
     */
    private function assignPaymentMethod($request, $paymentMethod)
    {
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
    }

    /**
     * @param  $paymentMethod
     */
    private function revokePaymentMethod($paymentMethod)
    {
        $this->userPaymentMethodRepository->query()->where([
            'payment_method_id' => $paymentMethod['id'],
        ])->delete();

        $this->customerPaymentMethodRepository->query()->where([
            'payment_method_id' => $paymentMethod['id'],
        ])->delete();
    }
}