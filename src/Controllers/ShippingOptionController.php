<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Doctrine\ORM\EntityRepository;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\ShippingOption;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Requests\ShippingOptionCreateRequest;
use Railroad\Ecommerce\Requests\ShippingOptionUpdateRequest;
use Railroad\Ecommerce\Services\JsonApiHydrator;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Services\PermissionService;

class ShippingOptionController extends BaseController
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
     * @var EntityRepository
     */
    private $shippingOptionRepository;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * ShippingOptionController constructor.
     *
     * @param EcommerceEntityManager $entityManager
     * @param JsonApiHydrator $jsonApiHydrator
     * @param PermissionService $permissionService
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        JsonApiHydrator $jsonApiHydrator,
        PermissionService $permissionService
    ) {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->jsonApiHydrator = $jsonApiHydrator;
        $this->shippingOptionRepository = $this->entityManager
                ->getRepository(ShippingOption::class);
        $this->permissionService = $permissionService;
    }

    /**
     * Pull shipping options
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.shipping.options');

        $alias = 's';
        $first = ($request->get('page', 1) - 1) * $request->get('limit', 10);
        $orderBy = $request->get('order_by_column', 'created_at');
        if (
            strpos($orderBy, '_') !== false
            || strpos($orderBy, '-') !== false
        ) {
            $orderBy = camel_case($orderBy);
        }
        $orderBy = $alias . '.' . $orderBy;

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->shippingOptionRepository->createQueryBuilder($alias);

        $qb
            ->setMaxResults($request->get('limit', 10))
            ->setFirstResult($first)
            ->orderBy($orderBy, $request->get('order_by_direction', 'desc'));

        $shippingOptions = $qb->getQuery()->getResult();

        return ResponseService::shippingOption($shippingOptions, $qb);
    }

    /**
     * Create a new shipping option and return it in JSON format
     *
     * @param ShippingOptionCreateRequest $request
     *
     * @return JsonResponse
     */
    public function store(ShippingOptionCreateRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'create.shipping.option');

        $shippingOption = new ShippingOption();

        $this->jsonApiHydrator->hydrate(
            $shippingOption,
            $request->onlyAllowed()
        );

        $this->entityManager->persist($shippingOption);
        $this->entityManager->flush();

        return ResponseService::shippingOption($shippingOption);
    }

    /**
     * Update a shipping option based on id and return it in JSON format
     * or proper exception if the shipping option not exist
     *
     * @param ShippingOptionUpdateRequest $request
     * @param int $shippingOptionId
     *
     * @return JsonResponse
     */
    public function update(
        ShippingOptionUpdateRequest $request,
        $shippingOptionId
    ) {
        $this->permissionService->canOrThrow(auth()->id(), 'edit.shipping.option');

        $shippingOption = $this->shippingOptionRepository
                                ->find($shippingOptionId);

        throw_if(
            is_null($shippingOption),
            new NotFoundException(
                'Update failed, shipping option not found with id: ' .
                $shippingOptionId
            )
        );

        $this->jsonApiHydrator
                ->hydrate($shippingOption, $request->onlyAllowed());

        $this->entityManager->flush();

        return ResponseService::shippingOption($shippingOption);
    }

    /**
     * Delete a shipping option if exist in the database.
     * Throw proper exception if the shipping option not exist in the database or a json response with status 204.
     *
     * @param integer $shippingOptionId
     *
     * @return JsonResponse
     */
    public function delete($shippingOptionId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'delete.shipping.option');

        $shippingOption = $this->shippingOptionRepository
                                ->find($shippingOptionId);

        throw_if(
            !$shippingOption,
            new NotFoundException(
                'Delete failed, shipping option not found with id: ' .
                $shippingOptionId
            )
        );

        $shippingCosts = $shippingOption->getShippingCostsWeightRanges();

        foreach ($shippingCosts as $shippingCost) {
            $this->entityManager->remove($shippingCost);
        }

        $this->entityManager->remove($shippingOption);
        $this->entityManager->flush();

        return ResponseService::empty(204);
    }
}