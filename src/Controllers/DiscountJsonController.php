<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repositories\DiscountRepository;
use Railroad\Ecommerce\Requests\DiscountCreateRequest;
use Railroad\Ecommerce\Requests\DiscountUpdateRequest;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Permissions\Services\PermissionService;

class DiscountJsonController extends BaseController
{
    /**
     * @var \Railroad\Ecommerce\Repositories\DiscountRepository
     */
    private $discountRepository;

    /**
     * @var \Railroad\Permissions\Services\PermissionService
     */
    private $permissionService;

    /**
     * DiscountJsonController constructor.
     *
     * @param \Railroad\Ecommerce\Repositories\DiscountRepository $discountRepository
     * @param \Railroad\Permissions\Services\PermissionService    $permissionService
     */
    public function __construct(DiscountRepository $discountRepository, PermissionService $permissionService)
    {
        parent::__construct();

        $this->discountRepository = $discountRepository;
        $this->permissionService  = $permissionService;
    }

    /** Pull discounts
     * @param \Illuminate\Http\Request $request
     * @return sonResponse
     */
    public function index(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.discounts');

        $discounts = $this->discountRepository->query()
            ->select(
                ConfigService::$tableDiscount . '.*',
                ConfigService::$tableProduct . '.name as productName'
            )
            ->leftJoin(
                ConfigService::$tableProduct,
                ConfigService::$tableDiscount . '.product_id',
                '=',
                ConfigService::$tableProduct . '.id'
            )
            ->limit($request->get('limit', 100))
            ->skip(($request->get('page', 1) - 1) * $request->get('limit', 100))
            ->orderBy($request->get('order_by_column', 'created_on'), $request->get('order_by_direction', 'desc'))
            ->get();
        $discountsCount = $this->discountRepository->query()->count();

        return reply()->json($discounts, [
            'totalResults' => $discountsCount
        ]);
    }

    /** Pull discount
     * @param \Illuminate\Http\Request $request
     * @param  int                     $discountId
     * @return JsonResponse
     */
    public function show(Request $request, $discountId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.discounts');

        $discount = $this->discountRepository->query()
            ->select(
                ConfigService::$tableDiscount . '.*',
                ConfigService::$tableProduct . '.name as productName'
            )
            ->leftJoin(
                ConfigService::$tableProduct,
                ConfigService::$tableDiscount . '.product_id',
                '=',
                ConfigService::$tableProduct . '.id'
            )
            ->where(ConfigService::$tableDiscount . '.id', $discountId)
            ->first();

        throw_if(
            is_null($discount),
            new NotFoundException('Pull failed, discount not found with id: ' . $discountId)
        );

        return reply()->json($discount);
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