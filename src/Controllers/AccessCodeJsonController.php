<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\Request;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Providers\UserProviderInterface;
use Railroad\Ecommerce\Repositories\AccessCodeRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Requests\AccessCodeJsonClaimRequest;
use Railroad\Ecommerce\Requests\AccessCodeReleaseRequest;
use Railroad\Ecommerce\Services\AccessCodeService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Permissions\Services\PermissionService;
use Railroad\Usora\Services\ConfigService as UsoraConfigService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class AccessCodeJsonController extends BaseController
{
    /**
     * @var AccessCodeRepository
     */
    private $accessCodeRepository;

    /**
     * @var AccessCodeService
     */
    private $accessCodeService;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * AccessCodeJsonController constructor.
     *
     * @param AccessCodeService $accessCodeService
     * @param AccessCodeRepository $accessCodeRepository
     * @param PermissionService $permissionService
     * @param ProductRepository $productRepository
     */
    public function __construct(
        AccessCodeService $accessCodeService,
        AccessCodeRepository $accessCodeRepository,
        PermissionService $permissionService,
        ProductRepository $productRepository
    ) {
        parent::__construct();

        $this->accessCodeService = $accessCodeService;
        $this->accessCodeRepository = $accessCodeRepository;
        $this->permissionService = $permissionService;
        $this->productRepository = $productRepository;
        $this->userProvider = app()->make(UserProviderInterface::class);

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
                ConfigService::$tableAccessCode . '.*'
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
            ->select(
                ConfigService::$tableAccessCode . '.*'
            )
            ->whereIn(
                'brand',
                $request->get('brands', [ConfigService::$availableBrands])
            )
            ->where('code', 'like', '%' . $request->get('term') . '%')
            ->get();

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
            ['meta' => ['products' => $products]]
        );
    }

    /**
     * Claim an access code
     *
     * @param AccessCodeJsonClaimRequest $request
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function claim(AccessCodeJsonClaimRequest $request)
    {
        $this->permissionService->canOrThrow(
            auth()->id(),
            'claim.access_codes'
        );

        if (empty($request->get('claim_for_user_id'))) {
            throw new NotFoundHttpException();
        }

        $accessCode = $this->accessCodeService
            ->claim($request->get('access_code'), $request->get('claim_for_user_id'));

        return reply()->json($accessCode);
    }

    /**
     * Release an access code
     *
     * @param AccessCodeReleaseRequest $request
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function release(AccessCodeReleaseRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'release.access_codes');

        $accessCode = $this->accessCodeRepository->update(
            $request->get('access_code_id'),
            [
                'is_claimed' => false,
                'claimer_id' => null,
                'claimed_on' => null
            ]
        );

        return reply()->json($accessCode);
    }
}
