<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\AccessCode;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Exceptions\NotFoundException;
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
     * @var EntityRepository
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

        // parse request params and prepare db query parms
        $alias = 'a';
        $aliasClaimer = 'c';
        $orderBy = $request->get('order_by_column', 'created_at');
        if (
            strpos($orderBy, '_') !== false
            || strpos($orderBy, '-') !== false
        ) {
            $orderBy = camel_case($orderBy);
        }
        $orderBy = $alias . '.' . $orderBy;
        $first = ($request->get('page', 1) - 1) * $request->get('limit', 10);
        $brands = $request->get('brands', [ConfigService::$availableBrands]);

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->accessCodeRepository->createQueryBuilder($alias);

        $qb
            ->select([$alias, $aliasClaimer])
            ->join($alias . '.claimer', $aliasClaimer)
            ->setMaxResults($request->get('limit', 10))
            ->setFirstResult($first)
            ->where($qb->expr()->in($alias . '.brand', ':brands'))
            ->orderBy($orderBy, $request->get('order_by_direction', 'desc'));

        /**
         * @var $q \Doctrine\ORM\Query
         */
        $q = $qb->getQuery();

        $q->setParameter('brands', $brands);

        $accessCodes = $q->getResult();

        // fetch related products, as a dictionary
        $productRepository = $this->entityManager
                                ->getRepository(Product::class);

        $products = $productRepository->getAccessCodesProducts($accessCodes);

        // map products to ease access
        $productsMap = [];

        foreach ($products as $product) {
            $productsMap[$product->getId()] = $product;
        }

        return ResponseService::decoratedAccessCode($accessCodes, $productsMap);
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

        $alias = 'a';
        $aliasClaimer = 'c';
        $brands = $request->get('brands', [ConfigService::$availableBrands]);

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->accessCodeRepository->createQueryBuilder($alias);
        $qb
            ->select([$alias, $aliasClaimer])
            ->join($alias . '.claimer', $aliasClaimer)
            ->where($qb->expr()->in($alias . '.brand', ':brands'))
            ->andWhere($qb->expr()->like($alias . '.code', ':term'));

        /**
         * @var $q \Doctrine\ORM\Query
         */
        $q = $qb->getQuery();

        $q->setParameter('brands', $brands);
        $q->setParameter('term', '%' . $request->get('term') . '%');

        $accessCodes = $q->getResult();

        // fetch related products, as a dictionary
        $productRepository = $this->entityManager
                                ->getRepository(Product::class);

        $products = $productRepository->getAccessCodesProducts($accessCodes);

        // map products to ease access
        $productsMap = [];

        foreach ($products as $product) {
            $productsMap[$product->getId()] = $product;
        }

        return ResponseService::decoratedAccessCode($accessCodes, $productsMap);
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
