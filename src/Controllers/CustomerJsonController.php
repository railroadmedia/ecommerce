<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Repositories\CustomerRepository;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Exceptions\NotAllowedException;
use Railroad\Permissions\Services\PermissionService;

class CustomerJsonController extends Controller
{
    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * CustomerJsonController constructor.
     *
     * @param CustomerRepository $customerRepository
     * @param PermissionService $permissionService
     */
    public function __construct(
        CustomerRepository $customerRepository,
        OrderRepository $orderRepository,
        PermissionService $permissionService
    )
    {
        $this->customerRepository = $customerRepository;
        $this->orderRepository = $orderRepository;
        $this->permissionService = $permissionService;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws NotAllowedException
     */
    public function index(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.customers');

        $customersAndBuilder = $this->customerRepository->indexByRequest($request);

        $customersOrders = $this->orderRepository->getCustomersOrders($customersAndBuilder->getResults());

        $customersOrdersMap = []; // map orders by customer id

        foreach ($customersOrders as $customersOrder) {
            $customersOrdersMap[$customersOrder->getCustomer()->getId()] = $customersOrder;
        }

        return ResponseService::customer(
                $customersAndBuilder->getResults(),
                $customersOrdersMap,
                $customersAndBuilder->getQueryBuilder()
            )
            ->respond(200);
    }
}
