<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Requests\RetentionStatsRequest;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Ecommerce\Services\RetentionStatsService;
use Railroad\Permissions\Exceptions\NotAllowedException;
use Railroad\Permissions\Services\PermissionService;

class RetentionJsonController extends Controller
{
    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var RetentionStatsService
     */
    private $retentionStatsService;

    /**
     * MembershipJsonController constructor.
     *
     * @param PermissionService $permissionService
     * @param RetentionStatsService $retentionStatsService
     */
    public function __construct(
        PermissionService $permissionService,
        RetentionStatsService $retentionStatsService
    )
    {
        $this->permissionService = $permissionService;
        $this->retentionStatsService = $retentionStatsService;
    }

    /**
     * @param RetentionStatsRequest $request
     * @return JsonResponse
     * @throws NotAllowedException
     */
    public function stats(RetentionStatsRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.retention-stats');

        $stats = $this->retentionStatsService->getStats($request);

        return ResponseService::retentionStats($stats)
            ->respond(200);
    }

    public function averageMembershipEnd(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.retention-stats');

        $stats = $this->retentionStatsService->getAverageMembershipEnd($request);

        return ResponseService::averageMembershipEnd($stats)
            ->respond(200);
    }
}
