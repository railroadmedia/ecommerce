<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repositories\DiscountRepository;
use Railroad\Ecommerce\Requests\DiscountCreateRequest;
use Railroad\Ecommerce\Requests\DiscountUpdateRequest;
use Railroad\Ecommerce\Responses\JsonResponse;
use Railroad\Permissions\Services\PermissionService;

class DiscountJsonController extends Controller
{
    /**
     * @var \Railroad\Ecommerce\Repositories\DiscountRepository
     */
    private $discountRepository;

    /**
     * @var \Railroad\Permissions\Services\PermissionService
     */
    private $permissionService;

    /**
     * DiscountJsonController constructor.
     *
     * @param \Railroad\Ecommerce\Repositories\DiscountRepository $discountRepository
     * @param \Railroad\Permissions\Services\PermissionService    $permissionService
     */
    public function __construct(DiscountRepository $discountRepository, PermissionService $permissionService)
    {
        $this->discountRepository = $discountRepository;
        $this->permissionService  = $permissionService;
    }

    /**
     * @param \Railroad\Ecommerce\Requests\DiscountCreateRequest $request
     * @return \Railroad\Ecommerce\Responses\JsonResponse
     */
    public function store(DiscountCreateRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'create.discount');

        $discount = $this->discountRepository->create(
            array_merge(
                $request->only(
                    [
                        'name',
                        'description',
                        'type',
                        'amount',
                        'active',
                    ]
                ),
                [
                    'created_on' => Carbon::now()->toDateTimeString(),
                ]
            )

        );

        return new JsonResponse($discount, 200);
    }

    /**
     * @param \Railroad\Ecommerce\Requests\DiscountUpdateRequest $request
     * @param                                                    $discountId
     * @return \Railroad\Ecommerce\Responses\JsonResponse
     */
    public function update(DiscountUpdateRequest $request, $discountId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'update.discount');

        $discount = $this->discountRepository->read($discountId);
        throw_if(
            is_null($discount),
            new NotFoundException('Update failed, discount not found with id: ' . $discountId)
        );

        //update discount with the data sent on the request
        $discount = $this->discountRepository->update(
            $discountId,
            array_merge(
                $request->only(
                    [
                        'name',
                        'description',
                        'type',
                        'amount',
                        'active',
                    ]
                ),
                [
                    'updated_on' => Carbon::now()->toDateTimeString(),
                ]
            )
        );

        return new JsonResponse($discount, 201);
    }

    /**
     * @param $discountId
     * @return \Railroad\Ecommerce\Responses\JsonResponse
     */
    public function delete($discountId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'delete.discount');

        $discount = $this->discountRepository->read($discountId);

        throw_if(
            is_null($discount),
            new NotFoundException('Delete failed, discount not found with id: ' . $discountId)
        );

       //TODO: delete discount criteria links
        $this->discountRepository->destroy($discountId);

        return new JsonResponse(null, 204);
    }
}