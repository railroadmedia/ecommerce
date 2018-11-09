<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\Request;
use Railroad\Ecommerce\Repositories\AccessCodeRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Usora\Services\ConfigService as UsoraConfigService;
use Railroad\Permissions\Services\PermissionService;
use Throwable;

class AccessCodeJsonController extends BaseController
{
    /**
     * @var AccessCodeRepository
     */
    private $accessCodeRepository;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * AccessCodeJsonController constructor.
     *
     * @param AccessCodeRepository $accessCodeRepository
     * @param PermissionService $permissionService
     */
    public function __construct(
        AccessCodeRepository $accessCodeRepository,
        PermissionService $permissionService,
        ProductRepository $productRepository
    ) {
        parent::__construct();

        $this->accessCodeRepository = $accessCodeRepository;
        $this->permissionService = $permissionService;
        $this->productRepository = $productRepository;
    }

    /**
     * Paginated list of access codes, for admins only
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function index(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.access_codes');

        $accessCodes = $this->accessCodeRepository->query()
            ->select(
                ConfigService::$tableAccessCode . '.*',
                UsoraConfigService::$tableUsers . '.email as claimer'
            )
            ->leftJoin(
                UsoraConfigService::$tableUsers,
                ConfigService::$tableAccessCode . '.claimer_id',
                '=',
                UsoraConfigService::$tableUsers . '.id'
            )
            ->whereIn(
                'brand',
                $request->get('brands', [ConfigService::$availableBrands])
            )
            ->limit($request->get('limit', 10))
            ->skip(($request->get('page', 1) - 1) * $request->get('limit', 10))
            ->orderBy(
                $request->get('order_by_column', 'created_on'),
                $request->get('order_by_direction', 'desc')
            )
            ->get();

        $accessCodesCount = $this->accessCodeRepository->query()->count();

        $productIds = [];

        foreach ($accessCodes as $accessCode) {
            $accessCodeProductIds = array_flip($accessCode['product_ids']);

            $productIds += $accessCodeProductIds;
        }

        $products = $this->productRepository
                        ->query()
                        ->whereIn('id', array_keys($productIds))
                        ->get();

        return reply()->json(
            $accessCodes,
            [
                'totalResults' => $accessCodesCount,
                'meta' => ['products' => $products]
            ]
        );
    }

    /**
     * Search for access codes, for admins only
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function search(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.access_codes');

        $accessCodes = $this->accessCodeRepository->query()
            ->whereIn('brand', $request->get('brands',[ConfigService::$availableBrands]))
            ->where('code', 'like', '%' . $request->get('term') . '%')
            ->get();

        return reply()->json($accessCodes);
    }
}
