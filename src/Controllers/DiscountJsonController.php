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
use Spatie\Fractal\Fractal;
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
     * @var \Railroad\Permissions\Services\PermissionService
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
    ) {
        $this->discountRepository = $discountRepository;
        $this->entityManager = $entityManager;
        $this->jsonApiHydrator = $jsonApiHydrator;
        $this->permissionService  = $permissionService;
    }

    /**
     * Pull discounts
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return Fractal
     *
     * @throws Throwable
     */
    public function index(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.discounts');

        // parse request params and prepare db query parms
        $alias = 'd';
        $aliasProduct = 'p';
        $orderBy = $request->get('order_by_column', 'created_at');
        if (
            strpos($orderBy, '_') !== false
            || strpos($orderBy, '-') !== false
        ) {
            $orderBy = camel_case($orderBy);
        }
        $orderBy = $alias . '.' . $orderBy;
        $first = ($request->get('page', 1) - 1) * $request->get('limit', 10);

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->entityManager->createQueryBuilder($alias);

        $qb
            ->select([$alias, $aliasProduct])
            ->from(Discount::class, $alias)
            ->join($alias . '.product', $aliasProduct)
            ->setMaxResults($request->get('limit', 10))
            ->setFirstResult($first)
            ->orderBy($orderBy, $request->get('order_by_direction', 'desc'));

        $discounts = $qb->getQuery()->getResult();

        return ResponseService::discount($discounts, $qb);
    }

    /**
     * Pull discount
     *
     * @param int $discountId
     *
     * @return Fractal
     *
     * @throws Throwable
     */
    public function show($discountId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.discounts');

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->entityManager->createQueryBuilder('d');

        $qb
            ->select(['d', 'p'])
            ->from(Discount::class, 'd')
            ->join('d.product', 'p')
            ->where($qb->expr()->eq('d.id', ':id'));

        /**
         * @var $q \Doctrine\ORM\Query
         */
        $q = $qb->getQuery();

        $q->setParameter('id', $discountId);

        /**
         * @var $discount Discount
         */
        $discount = $q->getOneOrNullResult();

        throw_if(
            is_null($discount),
            new NotFoundException('Pull failed, discount not found with id: ' . $discountId)
        );

        return ResponseService::discount($discount);
    }

    /**
     * @param DiscountCreateRequest $request
     *
     * @return Fractal
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

        return ResponseService::discount($discount);
    }

    /**
     * @param DiscountUpdateRequest $request
     * @param int $discountId
     *
     * @return Fractal
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

        return ResponseService::discount($discount);
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
