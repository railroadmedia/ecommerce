<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repositories\ShippingOptionRepository;
use Railroad\Ecommerce\Requests\ShippingOptionCreateRequest;
use Railroad\Ecommerce\Requests\ShippingOptionUpdateRequest;
use Railroad\Ecommerce\Responses\JsonResponse;
use Railroad\Permissions\Services\PermissionService;

class ShippingOptionController extends Controller
{
    /**
     * @var ShippingOptionRepository
     */
    private $shippingOptionRepository;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * ShippingOptionController constructor.
     *
     * @param ShippingOptionRepository $shippingOptionRepository
     * @param PermissionService $permissionService
     */
    public function __construct(
        ShippingOptionRepository $shippingOptionRepository,
        PermissionService $permissionService
    ) {
        $this->shippingOptionRepository = $shippingOptionRepository;
        $this->permissionService = $permissionService;
    }

    /**
     * Create a new shipping option and return it in JSON format
     *
     * @param ShippingOptionCreateRequest $request
     * @return JsonResponse
     */
    public function store(ShippingOptionCreateRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'create.shipping_option');

        $shippingOption = $this->shippingOptionRepository->create(
            array_merge(
                $request->only(
                    [
                        'country',
                        'priority',
                        'active',
                    ]
                ),
                ['created_on' => Carbon::now()->toDateTimeString()]
            )
        );

        return new JsonResponse($shippingOption, 200);
    }

    /**
     * Update a shipping option based on id and return it in JSON format
     * or proper exception if the shipping option not exist
     *
     * @param ShippingOptionUpdateRequest $request
     * @param integer $shippingOptionId
     * @return JsonResponse
     */
    public function update(ShippingOptionUpdateRequest $request, $shippingOptionId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'edit.shipping_option');

        $shippingOption = $this->shippingOptionRepository->update(
            $shippingOptionId,
            $request->only(['country', 'priority', 'active',])
        );

        //if the update method response it's null the shipping option not exist; we throw the proper exception
        throw_if(
            is_null($shippingOption),
            new NotFoundException('Update failed, shipping option not found with id: ' . $shippingOptionId)
        );

        return new JsonResponse($shippingOption, 201);
    }

    /**
     * Delete a shipping option if exist in the database.
     * Throw proper exception if the shipping option not exist in the database or a json response with status 204.
     *
     * @param integer $shippingOptionId
     * @return JsonResponse
     */
    public function delete($shippingOptionId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'delete.shipping_option');

        $results = $this->shippingOptionRepository->destroy($shippingOptionId);

        //if the delete method response it's null the shipping option not exist; we throw the proper exception
        throw_if(
            !$results,
            new NotFoundException('Delete failed, shipping option not found with id: ' . $shippingOptionId)
        );

        return new JsonResponse(null, 204);
    }
}