<?php

namespace Railroad\Ecommerce\Controllers;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\DoctrineArrayHydrator\JsonApiHydrator;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repositories\AddressRepository;
use Railroad\Ecommerce\Requests\AddressCreateRequest;
use Railroad\Ecommerce\Requests\AddressDeleteRequest;
use Railroad\Ecommerce\Requests\AddressUpdateRequest;
use Railroad\Ecommerce\Services\ConfigService;
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
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var PermissionService
     */
    private $permissionService;
    /**
     * @var JsonApiHydrator
     */
    private $jsonApiHydrator;

    /**
     * AddressJsonController constructor.
     *
     * @param EntityManager $entityManager
     * @param PermissionService $permissionService
     * @param JsonApiHydrator $jsonApiHydrator
     */
    public function __construct(
        EntityManager $entityManager,
        PermissionService $permissionService,
        JsonApiHydrator $jsonApiHydrator
    ) {
        $this->entityManager = $entityManager;
        $this->permissionService = $permissionService;
        $this->jsonApiHydrator = $jsonApiHydrator;

        $this->addressRepository = $this->entityManager->getRepository(Address::class);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws NotAllowedException
     */
    public function index(Request $request)
    {
        if ($request->get('user_id') !== auth()->id()) {
            $this->permissionService->canOrThrow(auth()->id(), 'pull.user.payment.method');
        }

        $addresses = $this->addressRepository->query()
            ->whereIn('brand', $request->get('brands', [ConfigService::$availableBrands]))
            ->where('user_id', $request->get('user_id', auth()->id()))
            ->get();

        return ResponseService::address($addresses)
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
     */
    public function store(AddressCreateRequest $request)
    {
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
        $address = $this->addressRepository->find($addressId);

        throw_if(
            is_null($address),
            new NotFoundException('Update failed, address not found with id: ' . $addressId)
        );

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
        $address = $this->addressRepository->read($addressId);
        throw_if(
            is_null($address),
            new NotFoundException('Delete failed, address not found with id: ' . $addressId)
        );

        throw_if(
            ((!$this->permissionService->canOrThrow(auth()->id(), 'delete.address')) &&
                (auth()->id() !== intval($address['user_id'])) &&
                ($request->get('customer_id', 0) !== $address['customer_id'])),
            new NotAllowedException('This action is unauthorized.')
        );

        $results = $this->addressRepository->destroy($addressId);

        //if the delete method response it's null the product not exist; we throw the proper exception

        throw_if(
            ($results === -1),
            new NotAllowedException('Delete failed, exists orders defined for the selected address.')
        );

        return reply()->json(null, ['code' => 204]);
    }
}