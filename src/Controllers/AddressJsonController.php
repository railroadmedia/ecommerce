<?php

namespace Railroad\Ecommerce\Controllers;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\AddressRepository;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Requests\AddressCreateRequest;
use Railroad\Ecommerce\Requests\AddressDeleteRequest;
use Railroad\Ecommerce\Requests\AddressUpdateRequest;
use Railroad\Ecommerce\Services\JsonApiHydrator;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Exceptions\NotAllowedException;
use Railroad\Permissions\Services\PermissionService;
use Throwable;

class AddressJsonController extends Controller
{
    /**
     * @var AddressRepository
     */
    private $addressRepository;

    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var JsonApiHydrator
     */
    private $jsonApiHydrator;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * AddressJsonController constructor.
     *
     * @param AddressRepository $addressRepository
     * @param OrderRepository $orderRepository
     * @param EcommerceEntityManager $entityManager
     * @param PermissionService $permissionService
     * @param JsonApiHydrator $jsonApiHydrator
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        AddressRepository $addressRepository,
        OrderRepository $orderRepository,
        EcommerceEntityManager $entityManager,
        PermissionService $permissionService,
        JsonApiHydrator $jsonApiHydrator,
        UserProviderInterface $userProvider
    )
    {
        $this->addressRepository = $addressRepository;
        $this->entityManager = $entityManager;
        $this->orderRepository = $orderRepository;
        $this->permissionService = $permissionService;
        $this->jsonApiHydrator = $jsonApiHydrator;
        $this->userProvider = $userProvider;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws NotAllowedException
     */
    public function index(Request $request)
    {
        $currentUserId = $this->userProvider->getCurrentUserId();

        if (
            (!empty($request->get('user_id')) && $request->get('user_id') !== $currentUserId)
            || !empty($request->get('customer_id'))
        ) {
            $this->permissionService->canOrThrow($currentUserId, 'pull.addresses');
        }

        $addressesAndBuilder = $this->addressRepository->indexByRequest($request, $currentUserId);

        return ResponseService::address(
            $addressesAndBuilder->getResults(),
            $addressesAndBuilder->getQueryBuilder()
        )
            ->respond(200);
    }

    /**
     * Call the method to store a new address based on request parameters.
     * Return a JsonResponse with the new created address.
     *
     * @param AddressCreateRequest $request
     * @return JsonResponse
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     * @throws Throwable
     */
    public function store(AddressCreateRequest $request)
    {
        if (!$this->permissionService->can(auth()->id(), 'store.address')) {
            if ($request->input('data.relationships.user.data.id') != auth()->id()) {

                throw new NotAllowedException(
                    'This action is unauthorized, users can only create addresses for themselves.'
                );
            }
        }

        $address = new Address();

        $this->jsonApiHydrator->hydrate($address, $request->onlyAllowed());

        $this->entityManager->persist($address);

        $this->entityManager->flush();

        return ResponseService::address($address)
            ->respond(201);
    }

    /**
     * Update an address based on address id and requests parameters.
     * Return - NotFoundException if the address not exists
     *        - NotAllowedException if the user have not rights to access it
     *        - JsonResponse with the updated address
     *
     * @param AddressUpdateRequest $request
     * @param int $addressId
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function update(AddressUpdateRequest $request, $addressId)
    {
        /** @var $address Address */
        $address = $this->addressRepository->find($addressId);

        throw_if(
            is_null($address),
            new NotFoundException(
                'Update failed, address not found with id: ' . $addressId
            )
        );

        if (!$this->permissionService->can(auth()->id(), 'update.address')) {
            if (($address->getUser() && auth()->id() !== intval(
                    $address->getUser()
                        ->getId()
                ))) {

                throw new NotAllowedException(
                    'This action is unauthorized, only the owning user can update this address.'
                );
            }

            if (($address->getCustomer() &&
                $request->input('data.relationships.customer.data.id') !==
                $address->getCustomer()
                    ->getId())) {

                throw new NotAllowedException('This action is unauthorized. You must pass the correct customer id.');
            }

            if (is_null($address->getUser()) && is_null($address->getCustomer())) {

                throw new NotAllowedException(
                    'This action is unauthorized, no user or customer is linked to the address.'
                );
            }
        }

        $this->jsonApiHydrator->hydrate($address, $request->onlyAllowed());

        $this->entityManager->flush();

        return ResponseService::address($address)
            ->respond(200);
    }

    /**
     * Delete an address based on the id.
     * Return - NotFoundException if the address not exists
     *        - NotAllowedException if the address it's in used (exists orders defined for the selected address)  or
     * the user have not rights to access it
     *        - JsonResponse with code 204 otherwise
     *
     * @param integer $addressId
     * @param AddressDeleteRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function delete($addressId, AddressDeleteRequest $request)
    {
        $address = $this->addressRepository->find($addressId);

        throw_if(
            is_null($address),
            new NotFoundException(
                'Delete failed, address not found with id: ' . $addressId
            )
        );

        if (!$this->permissionService->can(auth()->id(), 'update.address')) {
            if (($address->getUser() && auth()->id() !== intval(
                    $address->getUser()
                        ->getId()
                ))) {

                throw new NotAllowedException(
                    'This action is unauthorized, only the owning user can update this address.'
                );
            }

            if (($address->getCustomer() &&
                $request->input('customer_id') !==
                $address->getCustomer()
                    ->getId())) {

                throw new NotAllowedException('This action is unauthorized. You must pass the correct customer id.');
            }

            if (is_null($address->getUser()) && is_null($address->getCustomer())) {

                throw new NotAllowedException(
                    'This action is unauthorized, no user or customer is linked to the address.'
                );
            }
        }

        throw_if(
            $this->orderRepository->ordersWithAdressExist($address),
            new NotAllowedException(
                'Delete failed, orders found with selected address.'
            )
        );

        $this->entityManager->remove($address);
        $this->entityManager->flush();

        return ResponseService::empty(204);
    }
}