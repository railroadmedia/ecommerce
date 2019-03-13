<?php

namespace Railroad\Ecommerce\Controllers;

use Doctrine\ORM\EntityManager;
use Illuminate\Http\JsonResponse;
use Railroad\DoctrineArrayHydrator\JsonApiHydrator;
use Railroad\Ecommerce\Entities\DiscountCriteria;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Requests\DiscountCriteriaCreateRequest;
use Railroad\Ecommerce\Requests\DiscountCriteriaUpdateRequest;
use Railroad\Ecommerce\Repositories\DiscountCriteriaRepository;
use Railroad\Ecommerce\Repositories\DiscountRepository;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Services\PermissionService;
use Spatie\Fractal\Fractal;
use Throwable;

class DiscountCriteriaJsonController extends BaseController
{
    /**
     * @var DiscountCriteriaRepository
     */
    private $discountCriteriaRepository;

    /**
     * @var DiscountRepository
     */
    private $discountRepository;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var JsonApiHydrator
     */
    private $jsonApiHydrator;

    /**
     * @var \Railroad\Permissions\Services\PermissionService
     */
    private $permissionService;

    /**
     * DiscountCriteriaJsonController constructor.
     *
     * @param DiscountCriteriaRepository $discountCriteriaRepository
     * @param DiscountRepository $discountRepository
     * @param EntityManager $entityManager
     * @param JsonApiHydrator $jsonApiHydrator
     * @param PermissionService $permissionService
     */
    public function __construct(
        DiscountCriteriaRepository $discountCriteriaRepository,
        DiscountRepository $discountRepository,
        EntityManager $entityManager,
        JsonApiHydrator $jsonApiHydrator,
        PermissionService $permissionService
    ) {
        parent::__construct();

        $this->discountCriteriaRepository = $discountCriteriaRepository;
        $this->discountRepository = $discountRepository;
        $this->entityManager = $entityManager;
        $this->jsonApiHydrator = $jsonApiHydrator;
        $this->permissionService = $permissionService;
    }

    /**
     * @param \Railroad\Ecommerce\Requests\DiscountCriteriaCreateRequest $request
     * @param int $discountId
     *
     * @return Fractal
     *
     * @throws Throwable
     */
    public function store(DiscountCriteriaCreateRequest $request, $discountId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'create.discount.criteria');

        $discount = $this->discountRepository->find($discountId);

        throw_if(
            is_null($discount),
            new NotFoundException('Create discount criteria failed, discount not found with id: ' . $discountId)
        );

        $discountCriteria = new DiscountCriteria();

        $this->jsonApiHydrator->hydrate(
            $discountCriteria,
            $request->onlyAllowed()
        );

        $this->entityManager->persist($discountCriteria);

        $discountCriteria->setDiscount($discount);

        $this->entityManager->flush();

        return ResponseService::discountCriteria($discountCriteria);
    }

    /**
     * @param \Railroad\Ecommerce\Requests\DiscountCriteriaUpdateRequest $request
     * @param int $discountCriteriaId
     *
     * @return Fractal
     *
     * @throws Throwable
     */
    public function update(DiscountCriteriaUpdateRequest $request, $discountCriteriaId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'update.discount.criteria');

        $discountCriteria = $this->discountCriteriaRepository
                                ->find($discountCriteriaId);

        throw_if(
            is_null($discountCriteria),
            new NotFoundException(
                'Update discount criteria failed, ' .
                'discount criteria not found with id: ' . $discountCriteriaId
            )
        );

        $this->jsonApiHydrator->hydrate(
            $discountCriteria,
            $request->onlyAllowed()
        );

        $this->entityManager->flush();

        return ResponseService::discountCriteria($discountCriteria);
    }

    /**
     * @param int $discountCriteriaId
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function delete($discountCriteriaId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'delete.discount.criteria');

        $discountCriteria = $this->discountCriteriaRepository
                                ->find($discountCriteriaId);

        throw_if(
            is_null($discountCriteria),
            new NotFoundException(
                'Delete discount criteria failed, ' .
                'discount criteria not found with id: ' . $discountCriteriaId
            )
        );

        $this->entityManager->remove($discountCriteria);
        $this->entityManager->flush();

        return ResponseService::empty(204);
    }
}