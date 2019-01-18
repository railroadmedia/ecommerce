<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\AccessCode;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repository\AccessCodeRepository;
use Railroad\Ecommerce\Requests\AccessCodeJsonClaimRequest;
use Railroad\Ecommerce\Requests\AccessCodeReleaseRequest;
use Railroad\Ecommerce\Services\AccessCodeService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Services\PermissionService;
use Railroad\Usora\Entities\User;
use Railroad\Usora\Services\ConfigService as UsoraConfigService;
use Throwable;

class AccessCodeJsonController extends BaseController
{
    /**
     * @var AccessCodeService
     */
    private $accessCodeService;

    /**
     * @var AccessCodeRepository
     */
    private $accessCodeRepository;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * AccessCodeJsonController constructor.
     *
     * @param AccessCodeService $accessCodeService
     * @param EntityManager $entityManager
     * @param PermissionService $permissionService
     */
    public function __construct(
        AccessCodeService $accessCodeService,
        EntityManager $entityManager,
        PermissionService $permissionService
    ) {
        parent::__construct();

        $this->accessCodeService = $accessCodeService;
        $this->entityManager = $entityManager;
        $this->permissionService = $permissionService;

        $this->accessCodeRepository = $this->entityManager
                                        ->getRepository(AccessCode::class);
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

        // $accessCodes = $this->accessCodeRepository->query()
        //     ->select(
        //         ConfigService::$tableAccessCode . '.*',
        //         UsoraConfigService::$tableUsers . '.email as claimer'
        //     )
        //     ->leftJoin(
        //         UsoraConfigService::$tableUsers,
        //         ConfigService::$tableAccessCode . '.claimer_id',
        //         '=',
        //         UsoraConfigService::$tableUsers . '.id'
        //     )
        //     ->whereIn(
        //         'brand',
        //         $request->get('brands', [ConfigService::$availableBrands])
        //     )
        //     ->limit($request->get('limit', 10))
        //     ->skip(($request->get('page', 1) - 1) * $request->get('limit', 10))
        //     ->orderBy(
        //         $request->get('order_by_column', 'created_on'),
        //         $request->get('order_by_direction', 'desc')
        //     )
        //     ->get();

        // $accessCodesCount = $this->accessCodeRepository->query()->count();

        // $productIds = [];

        // foreach ($accessCodes as $accessCode) {
        //     $accessCodeProductIds = array_flip($accessCode['product_ids']);

        //     $productIds += $accessCodeProductIds;
        // }

        // $products = $this->productRepository
        //                 ->query()
        //                 ->whereIn('id', array_keys($productIds))
        //                 ->get();

        // return reply()->json(
        //     $accessCodes,
        //     [
        //         'totalResults' => $accessCodesCount,
        //         'meta' => ['products' => $products]
        //     ]
        // );
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

        // $accessCodes = $this->accessCodeRepository->query()
        //     ->select(
        //         ConfigService::$tableAccessCode . '.*',
        //         UsoraConfigService::$tableUsers . '.email as claimer'
        //     )
        //     ->leftJoin(
        //         UsoraConfigService::$tableUsers,
        //         ConfigService::$tableAccessCode . '.claimer_id',
        //         '=',
        //         UsoraConfigService::$tableUsers . '.id'
        //     )
        //     ->whereIn(
        //         'brand',
        //         $request->get('brands', [ConfigService::$availableBrands])
        //     )
        //     ->where('code', 'like', '%' . $request->get('term') . '%')
        //     ->get();

        // $productIds = [];

        // foreach ($accessCodes as $accessCode) {
        //     $accessCodeProductIds = array_flip($accessCode['product_ids']);

        //     $productIds += $accessCodeProductIds;
        // }

        // $products = $this->productRepository
        //                 ->query()
        //                 ->whereIn('id', array_keys($productIds))
        //                 ->get();

        // return reply()->json(
        //     $accessCodes,
        //     ['meta' => ['products' => $products]]
        // );
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

        $userRepository = $this->entityManager->getRepository(User::class);

        $user = $userRepository->findOneBy(
            ['email' => $request->get('claim_for_user_email')]
        );

        throw_if(
            is_null($user),
            new NotFoundException(
                'Claim failed, user not found with email: '
                . $request->get('claim_for_user_email')
            )
        );

        $accessCode = $this->accessCodeRepository
            ->findOneBy(['code' => $request->get('access_code')]);

        $claimedAccessCode = $this->accessCodeService
            ->claim($accessCode, $user);

        return ResponseService::accessCode($accessCode)->respond(200);
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

        $accessCode = $this->accessCodeRepository
                        ->find($request->get('access_code_id'));
        $accessCode
            ->setIsClaimed(false)
            ->setClaimer(null)
            ->setClaimedOn(null)
            ->setUpdatedAt(Carbon::now());

        $this->entityManager->flush();

        return ResponseService::accessCode($accessCode)->respond(200);
    }
}
