<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Services\AccountingService;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Exceptions\NotAllowedException;
use Railroad\Permissions\Services\PermissionService;
use Throwable;

class AccountingJsonController extends Controller
{
    /**
     * @var AccountingService
     */
    private $accountingService;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * AccountingJsonController constructor.
     *
     * @param AccountingService $accountingService
     * @param PermissionService $permissionService
     */
    public function __construct(
        AccountingService $accountingService,
        PermissionService $permissionService
    )
    {
        $this->accountingService = $accountingService;
        $this->permissionService = $permissionService;
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws NotAllowedException
     * @throws Throwable
     */
    public function productTotals(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.accounting');

        $accountingProductsTotals = $this->accountingService->indexByRequest($request);

        return ResponseService::accountingProductsTotals($accountingProductsTotals)
            ->respond(200);
    }
}
