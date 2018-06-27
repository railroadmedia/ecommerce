<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repositories\ShippingCostsRepository;
use Railroad\Ecommerce\Requests\ShippingCostCreateRequest;
use Railroad\Ecommerce\Requests\ShippingCostUpdateRequest;
use Railroad\Ecommerce\Responses\JsonResponse;
use Railroad\Permissions\Services\PermissionService;

class ShippingCostsWeightRangeController extends BaseController
{
    /**
     * @var ShippingCostsRepository
     */
    private $shippingCostsRepository;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * ShippingCostsWeightRangeController constructor.
     *
     * @param ShippingCostsRepository $shippingCostsRepository
     */
    public function __construct(ShippingCostsRepository $shippingCostsRepository, PermissionService $permissionService)
    {
        parent::__construct();

        $this->shippingCostsRepository = $shippingCostsRepository;
        $this->permissionService = $permissionService;
    }

    /**
     * Store a shipping cost weight range in the database and return it in JSON format if the shipping option exist.
     * Return a JSON response with the shopping cost weight range or throw the proper exception.
     *
     * @param ShippingCostCreateRequest $request
     * @return JsonResponse
     */
    public function store(ShippingCostCreateRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'create.shipping_cost');

        $shippingCosts = $this->shippingCostsRepository->create(
            array_merge(
            $request->only(
                [
                    'shipping_option_id',
                    'min',
                    'max',
                    'price',
                ]
            ),
            [
                'created_on' => Carbon::now()->toDateTimeString()
            ]
            )
        );

        return reply()->json($shippingCosts);
    }

    /**
     * Update a shipping cost weight range based on id and return it in JSON
     * format or proper exception if the shipping cost weight range not exist
     *
     * @param ShippingCostUpdateRequest $request
     * @param integer $shippingCostId
     * @return JsonResponse
     */
    public function update(ShippingCostUpdateRequest $request, $shippingCostId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'edit.shipping_cost');

        $shippingCost = $this->shippingCostsRepository->update(
            $shippingCostId,
            array_merge(
                $request->only(
                    [
                        'shipping_option_id',
                        'min',
                        'max',
                        'price',
                    ]
                ),
                ['updated_on' => Carbon::now()->toDateTimeString()]
            )
        );

        throw_if(
            is_null($shippingCost),
            new NotFoundException('Update failed, shipping cost weight range not found with id: ' . $shippingCostId)
        );

        return reply()->json($shippingCost, [
            'code' => 201
        ]);
    }

    /**
     * Delete a shipping cost weight range if exist in the database.
     * Throw proper exception if the shipping cost weight range not exist in the database or a json response with
     * status 204.
     *
     * @param integer $shippingCostId
     * @return JsonResponse
     */
    public function delete($shippingCostId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'delete.shipping_cost');

        $results = $this->shippingCostsRepository->destroy($shippingCostId);

        throw_if(
            !$results,
            new NotFoundException('Delete failed, shipping cost weight range not found with id: ' . $shippingCostId)
        );

        return reply()->json(null, [
            'code' => 204
        ]);
    }
}