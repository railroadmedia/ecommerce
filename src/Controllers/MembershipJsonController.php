<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Repositories\MembershipStatsRepository;
use Railroad\Ecommerce\Requests\MembershipStatsRequest;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Exceptions\NotAllowedException;
use Railroad\Permissions\Services\PermissionService;

class MembershipJsonController extends Controller
{
    /**
     * @var MembershipStatsRepository
     */
    private $membershipStatsRepository;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * MembershipJsonController constructor.
     *
     * @param MembershipStatsRepository $membershipStatsRepository
     * @param PermissionService $permissionService
     */
    public function __construct(
        MembershipStatsRepository $membershipStatsRepository,
        PermissionService $permissionService
    )
    {
        $this->membershipStatsRepository = $membershipStatsRepository;
        $this->permissionService = $permissionService;
    }

    /**
     * @param MembershipStatsRequest $request
     * @return JsonResponse
     * @throws NotAllowedException
     */
    public function stats(MembershipStatsRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.membership-stats');

        $smallDate = $request->get(
            'small_date_time',
            Carbon::now()
                ->subDays(2)
                ->toDateTimeString()
        );

        $smallDateTime =
            Carbon::parse($smallDate)
                ->startOfDay();

        $bigDate = $request->get(
            'big_date_time',
            Carbon::yesterday()
                ->toDateTimeString()
        );

        $bigDateTime =
            Carbon::parse($bigDate)
                ->endOfDay();

        $intervalType = $request->get('interval_type');

        $stats = $this->membershipStatsRepository->getStats($smallDateTime, $bigDateTime, $intervalType);

        // dd($stats);

        return ResponseService::membershipStats($stats)
            ->respond(200);
    }
}
