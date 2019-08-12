<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Ecommerce\Services\StatsService;
use Railroad\Permissions\Services\PermissionService;
use Throwable;

class StatsController extends Controller
{
    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var StatsService
     */
    private $statsService;

    /**
     * StatsController constructor.
     *
     * @param PermissionService $permissionService
     * @param StatsService $statsService
     */
    public function __construct(
        PermissionService $permissionService,
        StatsService $statsService
    )
    {
        $this->permissionService = $permissionService;
        $this->statsService = $statsService;
    }

    /**
     * return the daily statistics
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function dailyStatistics(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.daily-statistics');

        $dailyStatistics = $this->statsService->indexByRequest($request);

        return ResponseService::dailyStatistics($dailyStatistics)->respond(200);
    }
}
