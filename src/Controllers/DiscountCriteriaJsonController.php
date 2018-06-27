<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repositories\DiscountCriteriaRepository;
use Railroad\Ecommerce\Repositories\DiscountRepository;
use Railroad\Ecommerce\Requests\DiscountCriteriaCreateRequest;
use Railroad\Ecommerce\Requests\DiscountCriteriaUpdateRequest;
use Railroad\Ecommerce\Responses\JsonResponse;
use Railroad\Permissions\Services\PermissionService;

class DiscountCriteriaJsonController extends BaseController
{
    /**
     * @var \Railroad\Ecommerce\Repositories\DiscountCriteriaRepository
     */
    private $discountCriteriaRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\DiscountRepository
     */
    private $discountRepository;

    /**
     * @var \Railroad\Permissions\Services\PermissionService
     */
    private $permissionService;

    /**
     * DiscountCriteriaJsonController constructor.
     *
     * @param \Railroad\Ecommerce\Repositories\DiscountCriteriaRepository $discountCriteriaRepository
     * @param \Railroad\Permissions\Services\PermissionService            $permissionService
     */
    public function __construct(
        DiscountCriteriaRepository $discountCriteriaRepository,
        DiscountRepository $discountRepository,
        PermissionService $permissionService
    ) {
        parent::__construct();

        $this->discountCriteriaRepository = $discountCriteriaRepository;
        $this->discountRepository         = $discountRepository;
        $this->permissionService          = $permissionService;
    }

    /**
     * @param \Railroad\Ecommerce\Requests\DiscountCriteriaCreateRequest $request
     * @param                                                            $discountId
     * @return \Railroad\Ecommerce\Controllers\JsonResponse
     */
    public function store(DiscountCriteriaCreateRequest $request, $discountId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'create.discount.criteria');

        $discount = $this->discountRepository->read($discountId);
        throw_if(
            is_null($discount),
            new NotFoundException('Create discount criteria failed, discount not found with id: ' . $discountId)
        );

        $discountCriteria = $this->discountCriteriaRepository->create(
            array_merge(
                $request->only(
                    [
                        'name',
                        'product_id',
                        'type',
                        'min',
                        'max',
                    ]
                ),
                [
                    'discount_id' => $discountId,
                    'created_on' => Carbon::now()->toDateTimeString(),
                ]
            )

        );

        return new JsonResponse($discountCriteria, 200);

    }

    /**
     * @param \Railroad\Ecommerce\Requests\DiscountCriteriaUpdateRequest $request
     * @param                                                            $discountCriteriaId
     * @return \Railroad\Ecommerce\Controllers\JsonResponse
     */
    public function update(DiscountCriteriaUpdateRequest $request, $discountCriteriaId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'update.discount.criteria');

        $discountCriteria = $this->discountCriteriaRepository->read($discountCriteriaId);
        throw_if(
            is_null($discountCriteria),
            new NotFoundException('Update discount criteria failed, discount criteria not found with id: ' . $discountCriteriaId)
        );

        //update discount criteria with the data sent on the request
        $discountCriteria = $this->discountCriteriaRepository->update(
            $discountCriteriaId,
            array_merge(
                $request->only(
                    [
                        'name',
                        'product_id',
                        'type',
                        'min',
                        'max',
                    ]
                ),
                [
                    'updated_on' => Carbon::now()->toDateTimeString(),
                ]
            )
        );

        return new JsonResponse($discountCriteria, 201);
    }

    /**
     * @param $discountCriteriaId
     * @return \Railroad\Ecommerce\Controllers\JsonResponse
     */
    public function delete($discountCriteriaId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'delete.discount.criteria');

        $discountCriteria = $this->discountCriteriaRepository->read($discountCriteriaId);
        throw_if(
            is_null($discountCriteria),
            new NotFoundException('Delete discount criteria failed, discount criteria not found with id: ' . $discountCriteriaId)
        );

        $this->discountCriteriaRepository->destroy($discountCriteriaId);

        return new JsonResponse(null, 204);
    }
}