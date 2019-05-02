<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Entities\DiscountCriteria;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Requests\DiscountCriteriaCreateRequest;
use Railroad\Ecommerce\Requests\DiscountCriteriaUpdateRequest;
use Railroad\Ecommerce\Repositories\DiscountCriteriaRepository;
use Railroad\Ecommerce\Repositories\DiscountRepository;
use Railroad\Ecommerce\Services\JsonApiHydrator;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Services\PermissionService;
use Throwable;

class DiscountCriteriaJsonController extends Controller
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
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var JsonApiHydrator
     */
    private $jsonApiHydrator;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * DiscountCriteriaJsonController constructor.
     *
     * @param DiscountCriteriaRepository $discountCriteriaRepository
     * @param DiscountRepository $discountRepository
     * @param EcommerceEntityManager $entityManager
     * @param JsonApiHydrator $jsonApiHydrator
     * @param PermissionService $permissionService
     */
    public function __construct(
        DiscountCriteriaRepository $discountCriteriaRepository,
        DiscountRepository $discountRepository,
        EcommerceEntityManager $entityManager,
        JsonApiHydrator $jsonApiHydrator,
        PermissionService $permissionService
    ) {
        $this->discountCriteriaRepository = $discountCriteriaRepository;
        $this->discountRepository = $discountRepository;
        $this->entityManager = $entityManager;
        $this->jsonApiHydrator = $jsonApiHydrator;
        $this->permissionService = $permissionService;
    }

    /**
     * @param DiscountCriteriaCreateRequest $request
     * @param int $discountId
     *
     * @return JsonResponse
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

        return ResponseService::discountCriteria($discountCriteria)->respond(201);
    }

    /**
     * @param DiscountCriteriaUpdateRequest $request
     * @param int $discountCriteriaId
     *
     * @return JsonResponse
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

        return ResponseService::discountCriteria($discountCriteria)->respond(200);
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