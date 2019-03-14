<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\JsonResponse;
use Railroad\Ecommerce\Entities\ShippingCostsWeightRange;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\ShippingCostsWeightRangeRepository;
use Railroad\Ecommerce\Requests\ShippingCostCreateRequest;
use Railroad\Ecommerce\Requests\ShippingCostUpdateRequest;
use Railroad\Ecommerce\Services\JsonApiHydrator;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Services\PermissionService;
use Spatie\Fractal\Fractal;
use Throwable;

class ShippingCostsWeightRangeController extends BaseController
{
    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var JsonApiHydrator
     */
    private $jsonApiHydrator;

    /**
     * @var ShippingCostsWeightRangeRepository
     */
    private $shippingCostsWeightRangeRepository;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * ShippingCostsWeightRangeController constructor.
     *
     * @param EcommerceEntityManager $entityManager
     * @param JsonApiHydrator $jsonApiHydrator
     * @param PermissionService $permissionService
     * @param ShippingCostsWeightRangeRepository $shippingCostsWeightRangeRepository
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        JsonApiHydrator $jsonApiHydrator,
        PermissionService $permissionService,
        ShippingCostsWeightRangeRepository $shippingCostsWeightRangeRepository
    ) {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->jsonApiHydrator = $jsonApiHydrator;
        $this->permissionService = $permissionService;
        $this->shippingCostsWeightRangeRepository = $shippingCostsWeightRangeRepository;
    }

    /**
     * Store a shipping cost weight range in the database and return it in JSON format if the shipping option exist.
     * Return a JSON response with the shopping cost weight range or throw the proper exception.
     *
     * @param ShippingCostCreateRequest $request
     *
     * @return Fractal
     *
     * @throws Throwable
     */
    public function store(ShippingCostCreateRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'create.shipping_cost');

        $shippingCost = new ShippingCostsWeightRange();

        $this->jsonApiHydrator->hydrate(
            $shippingCost,
            $request->onlyAllowed()
        );

        $this->entityManager->persist($shippingCost);
        $this->entityManager->flush();

        return ResponseService::shippingCost($shippingCost);
    }

    /**
     * Update a shipping cost weight range based on id and return it in JSON
     * format or proper exception if the shipping cost weight range not exist
     *
     * @param ShippingCostUpdateRequest $request
     * @param int $shippingCostId
     *
     * @return Fractal
     *
     * @throws Throwable
     */
    public function update(ShippingCostUpdateRequest $request, $shippingCostId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'edit.shipping_cost');

        $shippingCost = $this->shippingCostsWeightRangeRepository
                                ->find($shippingCostId);

        throw_if(
            is_null($shippingCost),
            new NotFoundException(
                'Update failed, shipping cost weight range ' .
                'not found with id: ' . $shippingCostId
            )
        );

        $this->jsonApiHydrator->hydrate(
            $shippingCost,
            $request->onlyAllowed()
        );

        $this->entityManager->flush();

        return ResponseService::shippingCost($shippingCost);
    }

    /**
     * Delete a shipping cost weight range if exist in the database.
     * Throw proper exception if the shipping cost weight range not exist in the database or a json response with
     * status 204.
     *
     * @param integer $shippingCostId
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function delete($shippingCostId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'delete.shipping_cost');

        $shippingCost = $this->shippingCostsWeightRangeRepository
                                    ->find($shippingCostId);

        throw_if(
            !$shippingCost,
            new NotFoundException(
                'Delete failed, shipping cost weight range ' .
                'not found with id: ' . $shippingCostId
            )
        );

        $this->entityManager->remove($shippingCost);
        $this->entityManager->flush();

        return ResponseService::empty(204);
    }
}