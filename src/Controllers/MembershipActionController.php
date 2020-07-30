<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Repositories\MembershipActionRepository;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Services\PermissionService;
use Throwable;

class MembershipActionController extends Controller
{
    /**
     * @var MembershipActionRepository
     */
    private $membershipActionRepository;
    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * AccessCodeController constructor.
     * @param MembershipActionRepository $membershipActionRepository
     * @param PermissionService $permissionService
     */
    public function __construct(
        MembershipActionRepository $membershipActionRepository,
        PermissionService $permissionService
    )
    {
        $this->membershipActionRepository = $membershipActionRepository;
        $this->permissionService = $permissionService;
    }

    /**
     * Paginated list of membership actions, typically for admins only
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function index(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.membership-actions');

        $membershipActionsAndBuilder = $this->membershipActionRepository->indexByRequest($request);

        return ResponseService::membershipActions(
            $membershipActionsAndBuilder->getResults(),
            $membershipActionsAndBuilder->getQueryBuilder()
        )
            ->respond(200);
    }

}
