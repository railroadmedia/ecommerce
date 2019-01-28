<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Illuminate\Http\Request;
use Railroad\DoctrineArrayHydrator\JsonApiHydrator;
use Railroad\Ecommerce\Entities\Discount;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repositories\DiscountRepository;
use Railroad\Ecommerce\Requests\DiscountCreateRequest;
use Railroad\Ecommerce\Requests\DiscountUpdateRequest;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Services\PermissionService;

class DiscountJsonController extends BaseController
{
    /**
     * @var EntityRepository
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
     * DiscountJsonController constructor.
     *
     * @param EntityManager $entityManager
     * @param JsonApiHydrator $jsonApiHydrator
     * @param \Railroad\Permissions\Services\PermissionService    $permissionService
     */
    public function __construct(
        EntityManager $entityManager,
        JsonApiHydrator $jsonApiHydrator,
        PermissionService $permissionService
    ) {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->jsonApiHydrator = $jsonApiHydrator;
        $this->discountRepository = $this->entityManager
                                        ->getRepository(Discount::class);
        $this->permissionService  = $permissionService;
    }

    /**
     * Pull discounts
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return sonResponse
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
        $qb = $this->discountRepository->createQueryBuilder($alias);

        $qb
            ->select([$alias, $aliasProduct])
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
     * @param \Illuminate\Http\Request $request
     * @param  int                     $discountId
     *
     * @return JsonResponse
     */
    public function show(Request $request, $discountId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.discounts');

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->discountRepository->createQueryBuilder('d');

        $qb
            ->select(['d', 'p'])
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
     * @param \Railroad\Ecommerce\Requests\DiscountCreateRequest $request
     * @return JsonResponse
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
                        'product_id',
                        'product_category',
                        'active',
                        'visible',
                    ]
                ),
                [
                    'created_on' => Carbon::now()->toDateTimeString(),
                ]
            )

        );
        return reply()->json($discount, [
            'code' => 200
        ]);
    }

    /**
     * @param \Railroad\Ecommerce\Requests\DiscountUpdateRequest $request
     * @param  int                                               $discountId
     * @return JsonResponse
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
                        'product_id',
                        'product_category',
                        'active',
                        'visible',
                    ]
                ),
                [
                    'updated_on' => Carbon::now()->toDateTimeString(),
                ]
            )
        );

        return reply()->json($discount, [
            'code' => 201
        ]);
    }

    /**
     * @param $discountId
     * @return JsonResponse
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

        return reply()->json(null, [
            'code' => 204
        ]);
    }
}