<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\Request;
use Railroad\Ecommerce\Repositories\AccessCodeRepository;
use Railroad\Ecommerce\Services\ConfigService;
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
     * AccessCodeJsonController constructor.
     *
     * @param AccessCodeRepository $accessCodeRepository
     * @param PermissionService $permissionService
     */
    public function __construct(
        AccessCodeRepository $accessCodeRepository,
        PermissionService $permissionService
    ) {
        parent::__construct();

        $this->accessCodeRepository = $accessCodeRepository;
        $this->permissionService = $permissionService;
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
            ->whereIn('brand', $request->get('brands',[ConfigService::$brand]))
            ->limit($request->get('limit', 10))
            ->skip(($request->get('page', 1) - 1) * $request->get('limit', 10))
            ->orderBy(
                $request->get('order_by_column', 'created_on'),
                $request->get('order_by_direction', 'desc')
            )
            ->get();

        $accessCodesCount = $this->accessCodeRepository->query()->count();

        return reply()->json(
            $accessCodes,
            [
                'totalResults' => $accessCodesCount,
            ]
        );
    }

    /**
     * Claim an access code
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function claim(Request $request)
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * Release an access code
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function release(Request $request)
    {
        throw new \BadMethodCallException('Not implemented');
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
            ->whereIn('brand', $request->get('brands',[ConfigService::$brand]))
            ->where('code', 'like', '%' . $request->get('term') . '%');

        return reply()->json($accessCodes);
    }
}
