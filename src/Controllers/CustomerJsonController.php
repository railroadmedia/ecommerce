<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\CustomerRepository;
use Railroad\Ecommerce\Requests\CustomerUpdateRequest;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Services\JsonApiHydrator;
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
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var JsonApiHydrator
     */
    private $jsonApiHydrator;

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
     * @param EcommerceEntityManager $entityManager
     * @param JsonApiHydrator $jsonApiHydrator
     * @param OrderRepository $orderRepository
     * @param PermissionService $permissionService
     */
    public function __construct(
        CustomerRepository $customerRepository,
        EcommerceEntityManager $entityManager,
        JsonApiHydrator $jsonApiHydrator,
        OrderRepository $orderRepository,
        PermissionService $permissionService
    )
    {
        $this->customerRepository = $customerRepository;
        $this->entityManager = $entityManager;
        $this->jsonApiHydrator = $jsonApiHydrator;
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

    /**
     * @param int $id
     * @return JsonResponse
     * @throws NotAllowedException
     */
    public function show($id)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.customers');

        $customer = $this->customerRepository->find($id);

        throw_if(
            is_null($customer),
            new NotFoundException(
                'Customer not found with id: ' . $id
            )
        );

        return ResponseService::customer($customer);
    }

    /**
     * @param CustomerUpdateRequest $request
     * @param int $customerId
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function update(CustomerUpdateRequest $request, $customerId)
    {
        $customer = $this->customerRepository->find($customerId);

        throw_if(
            is_null($customer),
            new NotFoundException(
                'Update failed, customer not found with id: ' . $customerId
            )
        );

        $this->permissionService->canOrThrow(auth()->id(), 'update.customers');

        $this->jsonApiHydrator->hydrate($customer, $request->onlyAllowed());

        $this->entityManager->flush();

        return ResponseService::customer($customer)
            ->respond(200);
    }
}
