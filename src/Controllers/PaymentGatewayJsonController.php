<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repositories\PaymentGatewayRepository;
use Railroad\Ecommerce\Requests\PaymentGatewayCreateRequest;
use Railroad\Ecommerce\Requests\PaymentGatewayUpdateRequest;
use Railroad\Ecommerce\Responses\JsonResponse;
use Railroad\Permissions\Exceptions\NotAllowedException;
use Railroad\Permissions\Services\PermissionService;

class PaymentGatewayJsonController extends Controller
{
    /**
     * @var PaymentGatewayRepository
     */
    private $paymentGatewayRepository;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * PaymentGatewayJsonController constructor.
     *
     * @param PaymentGatewayRepository $paymentGatewayRepository
     */
    public function __construct(
        PaymentGatewayRepository $paymentGatewayRepository,
        PermissionService $permissionService
    ) {
        $this->paymentGatewayRepository = $paymentGatewayRepository;
        $this->permissionService = $permissionService;
    }

    /**
     * Call the service method to create a new payment gateway based on request parameters.
     * Return a JsonResponse with the new created payment gateway
     *
     * @param  PaymentGatewayCreateRequest $request
     * @return JsonResponse
     * @throws NotAllowedException
     */
    public function store(PaymentGatewayCreateRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'create.payment_gateway');

        $paymentGateway = $this->paymentGatewayRepository->create(
            [
                'type' => $request->get('type'),
                'name' => $request->get('name'),
                'config' => $request->get('config'),
                'brand' => $request->get('brand'),
                'created_on' => Carbon::now()->toDateTimeString(),
                'updated_on' => Carbon::now()->toDateTimeString(),
            ]
        );

        return new JsonResponse($paymentGateway, 200);
    }

    /**
     * Update a payment gateway based on request data and payment gateway id.
     * Return - NotFoundException if the payment gateway doesn't exist or the user have not rights to access it
     *        - JsonResponse with the updated payment gateway
     *
     * @param  PaymentGatewayUpdateRequest $request
     * @param integer $paymentGatewayId
     * @return JsonResponse|NotFoundException
     * @throws NotAllowedException
     */
    public function update($paymentGatewayId, PaymentGatewayUpdateRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'edit.payment_gateway');

        $paymentGateway = $this->paymentGatewayRepository->update(
            $paymentGatewayId,
            array_merge(
                $request->only(
                    [
                        'brand', 'type', 'name', 'config'
                    ]
                ),
                [
                    'updated_on'  => Carbon::now()->toDateTimeString()
                ])
        );

        throw_if(
            is_null($paymentGateway),
            new NotFoundException('Update failed, payment gateway not found with id: ' . $paymentGatewayId)
        );

        return new JsonResponse($paymentGateway, 201);
    }

    /**
     * Delete a payment gateway and return a JsonResponse.
     *  Throw  - NotFoundException if the payment gateway not exist
     *         - NotAllowedException if the payment gateway it's in used
     *
     * @param integer $paymentGatewayId
     * @return JsonResponse
     * @throws NotAllowedException
     */
    public function delete($paymentGatewayId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'delete.payment_gateway');

        $results = $this->paymentGatewayRepository->destroy($paymentGatewayId);

        throw_if(
            !$results,
            new NotFoundException('Delete failed, could not find a payment gateway to delete.')
        );

        return new JsonResponse(null, 204);
    }
}