<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Railroad\DoctrineArrayHydrator\JsonApiHydrator;
use Railroad\Ecommerce\Entities\ShippingCostsWeightRange;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repositories\ShippingCostsRepository;
use Railroad\Ecommerce\Requests\ShippingCostCreateRequest;
use Railroad\Ecommerce\Requests\ShippingCostUpdateRequest;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Services\PermissionService;

class ShippingCostsWeightRangeController extends BaseController
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var JsonApiHydrator
     */
    private $jsonApiHydrator;

    /**
     * @var EntityRepository
     */
    private $shippingCostsRepository;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * ShippingCostsWeightRangeController constructor.
     *
     * @param EntityManager $entityManager
     * @param JsonApiHydrator $jsonApiHydrator
     * @param PermissionService $permissionService
     */
    public function __construct(
        EntityManager $entityManager,
        JsonApiHydrator $jsonApiHydrator,
        PermissionService $permissionService
    ) {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->jsonApiHydrator = $jsonApiHydrator;
        $this->shippingCostsRepository = $this->entityManager
                ->getRepository(ShippingCostsWeightRange::class);
        $this->permissionService = $permissionService;
    }

    /**
     * Store a shipping cost weight range in the database and return it in JSON format if the shipping option exist.
     * Return a JSON response with the shopping cost weight range or throw the proper exception.
     *
     * @param ShippingCostCreateRequest $request
     *
     * @return JsonResponse
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
     * @return JsonResponse
     */
    public function update(ShippingCostUpdateRequest $request, $shippingCostId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'edit.shipping_cost');

        $shippingCost = $this->shippingCostsRepository->find($shippingCostId);

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
     */
    public function delete($shippingCostId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'delete.shipping_cost');

        $shippingCost = $this->shippingCostsRepository->find($shippingCostId);

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