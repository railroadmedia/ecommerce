<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Entities\Discount;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\DiscountRepository;
use Railroad\Ecommerce\Requests\DiscountCreateRequest;
use Railroad\Ecommerce\Requests\DiscountUpdateRequest;
use Railroad\Ecommerce\Services\JsonApiHydrator;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Services\PermissionService;
use Throwable;

class DiscountJsonController extends Controller
{
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
     * DiscountJsonController constructor.
     *
     * @param DiscountRepository $discountRepository
     * @param EcommerceEntityManager $entityManager
     * @param JsonApiHydrator $jsonApiHydrator
     * @param PermissionService $permissionService
     */
    public function __construct(
        DiscountRepository $discountRepository,
        EcommerceEntityManager $entityManager,
        JsonApiHydrator $jsonApiHydrator,
        PermissionService $permissionService
    )
    {
        $this->discountRepository = $discountRepository;
        $this->entityManager = $entityManager;
        $this->jsonApiHydrator = $jsonApiHydrator;
        $this->permissionService = $permissionService;
    }

    /**
     * Pull discounts
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function index(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.discounts');

        $discountsAndBuilder = $this->discountRepository->indexByRequest($request);

        return ResponseService::discount($discountsAndBuilder->getResults(), $discountsAndBuilder->getQueryBuilder())
            ->respond(200);
    }

    /**
     * Pull discount
     *
     * @param int $discountId
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function show($discountId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.discounts');

        $discount = $this->discountRepository->find($discountId);

        throw_if(
            is_null($discount),
            new NotFoundException('Pull failed, discount not found with id: ' . $discountId)
        );

        return ResponseService::discount($discount)
            ->respond(200);
    }

    /**
     * @param DiscountCreateRequest $request
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function store(DiscountCreateRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'create.discount');

        $discount = new Discount();

        $this->jsonApiHydrator->hydrate($discount, $request->onlyAllowed());

        $this->entityManager->persist($discount);
        $this->entityManager->flush();

        return ResponseService::discount($discount)->respond(201);
    }

    /**
     * @param DiscountUpdateRequest $request
     * @param int $discountId
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function update(DiscountUpdateRequest $request, $discountId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'update.discount');

        $discount = $this->discountRepository->find($discountId);

        throw_if(
            is_null($discount),
            new NotFoundException('Update failed, discount not found with id: ' . $discountId)
        );

        $this->jsonApiHydrator->hydrate($discount, $request->onlyAllowed());

        $this->entityManager->flush();

        return ResponseService::discount($discount)->respond(200);
    }

    /**
     * @param int $discountId
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function delete($discountId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'delete.discount');

        $discount = $this->discountRepository->find($discountId);

        throw_if(
            is_null($discount),
            new NotFoundException('Delete failed, discount not found with id: ' . $discountId)
        );

        // TODO: delete discount criteria links
        $this->entityManager->remove($discount);
        $this->entityManager->flush();

        return ResponseService::empty(204);
    }
}
