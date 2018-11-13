<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\Request;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repositories\AccessCodeRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Requests\AccessCodeJsonClaimRequest;
use Railroad\Ecommerce\Requests\AccessCodeReleaseRequest;
use Railroad\Ecommerce\Services\AccessCodeService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Permissions\Services\PermissionService;
use Railroad\Usora\Services\ConfigService as UsoraConfigService;
use Railroad\Usora\Repositories\UserRepository;
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
     * @var UserRepository
     */
    private $userRepository;

    /**
     * AccessCodeJsonController constructor.
     *
     * @param AccessCodeService $accessCodeService
     * @param AccessCodeRepository $accessCodeRepository
     * @param PermissionService $permissionService
     * @param ProductRepository $productRepository
     * @param UserRepository $userRepository
     */
    public function __construct(
        AccessCodeService $accessCodeService,
        AccessCodeRepository $accessCodeRepository,
        PermissionService $permissionService,
        ProductRepository $productRepository,
        UserRepository $userRepository
    ) {
        parent::__construct();

        $this->accessCodeService = $accessCodeService;
        $this->accessCodeRepository = $accessCodeRepository;
        $this->permissionService = $permissionService;
        $this->productRepository = $productRepository;
        $this->userRepository = $userRepository;
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

        $user = $this->userRepository
            ->query()
            ->where('email', '=', $request->get('claim_for_user_email'))
            ->first();

        throw_if(
            is_null($user),
            new NotFoundException(
                'Claim failed, user not found with email: '
                . $request->get('claim_for_user_email')
            )
        );

        $accessCode = $this->accessCodeService
            ->claim($request->get('access_code'), $user);

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
