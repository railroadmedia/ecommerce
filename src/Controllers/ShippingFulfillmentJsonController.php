<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Entities\OrderItemFulfillment;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\OrderItemFulfillmentRepository;
use Railroad\Ecommerce\Requests\OrderFulfilledRequest;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Exceptions\NotAllowedException;
use Railroad\Permissions\Services\PermissionService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ShippingFulfillmentJsonController extends Controller
{
    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var OrderItemFulfillmentRepository
     */
    private $orderItemFulfillmentRepository;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * ShippingFulfillmentJsonController constructor.
     *
     * @param  EcommerceEntityManager  $entityManager
     * @param  OrderItemFulfillmentRepository  $orderItemFulfillmentRepository
     * @param  PermissionService  $permissionService
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        OrderItemFulfillmentRepository $orderItemFulfillmentRepository,
        PermissionService $permissionService
    ) {
        $this->entityManager = $entityManager;
        $this->orderItemFulfillmentRepository = $orderItemFulfillmentRepository;
        $this->permissionService = $permissionService;
    }

    /**
     * Pull shipping fulfillments. If the status it's set on the requests the results are filtered by status.
     *
     * @param  Request  $request
     *
     * @return JsonResponse|BinaryFileResponse
     *
     * @throws Throwable
     */
    public function index(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.fulfillments');

        $fulfillmentsAndBuilder = $this->orderItemFulfillmentRepository->indexByRequest($request);

        $fulfillments = $fulfillmentsAndBuilder->getResults();

        if ($request->has('csv') && $request->get('csv') == true) {
            $rows = [];

            foreach ($fulfillments as $fulfillment) {
                /** @var $fulfillment OrderItemFulfillment */
                $email = '';

                if ( ! empty(
                $fulfillment->getOrder()
                    ->getUser()
                )
                ) {
                    $email = $fulfillment->getOrder()
                        ->getUser()
                        ->getEmail();
                } elseif ( ! empty(
                $fulfillment->getOrder()
                    ->getCustomer()
                )
                ) {
                    $email = $fulfillment->getOrder()
                        ->getCustomer()
                        ->getEmail();
                }
                
                if (empty(
                $fulfillment->getOrder()
                    ->getShippingAddress()
                )
                ) {
                    error_log(
                        'Warning, order for fulfillment does not have shipping address. Order ID: '
                        .$fulfillment->getOrder()
                            ->getId()
                    );
                    continue;
                }

                $rows[] = [
                    $fulfillment->getOrder()
                        ->getId(),
                    Carbon::instance($fulfillment->getCreatedAt())
                        ->timezone($request->get('timezone', 'America/Los_Angeles'))
                        ->toDateTimeString(),
                    $email,
                    '',
                    '',
                    $fulfillment->getOrder()
                        ->getShippingAddress()
                        ->getFirstName(),
                    $fulfillment->getOrder()
                        ->getShippingAddress()
                        ->getLastName(),
                    $fulfillment->getOrder()
                        ->getShippingAddress()
                        ->getStreetLine1(),
                    $fulfillment->getOrder()
                        ->getShippingAddress()
                        ->getStreetLine2(),
                    $fulfillment->getOrder()
                        ->getShippingAddress()
                        ->getCity(),
                    $fulfillment->getOrder()
                        ->getShippingAddress()
                        ->getRegion(),
                    $fulfillment->getOrder()
                        ->getShippingAddress()
                        ->getZip(),
                    $fulfillment->getOrder()
                        ->getShippingAddress()
                        ->getCountry(),
                    $fulfillment->getStatus() == config('ecommerce.fulfillment_status_fulfilled'),
                    $fulfillment->getOrderItem()
                        ->getId(),
                    $fulfillment->getOrderItem()
                        ->getProduct()
                        ->getId(),
                    $fulfillment->getOrderItem()
                        ->getProduct()
                        ->getName(),
                    $fulfillment->getOrderItem()
                        ->getProduct()
                        ->getInventoryControlSku(),
                    $fulfillment->getOrderItem()
                        ->getProduct()
                        ->getFulfillmentSku(),
                    $fulfillment->getOrderItem()
                        ->getProduct()
                        ->getSku(),
                    $fulfillment->getOrderItem()
                        ->getProduct()
                        ->getWeight(),
                    $fulfillment->getOrderItem()
                        ->getQuantity(),
                    $fulfillment->getOrderItem()
                        ->getFinalPrice(),
                    $fulfillment->getStatus(),
                    '',
                    '',
                    ! empty($fulfillment->getFulfilledOn()) ? Carbon::instance($fulfillment->getFulfilledOn())
                        ->timezone($request->get('timezone', 'America/Los_Angeles'))
                        ->toDateTimeString() : '',
                    $fulfillment->getOrderItem()
                        ->getFinalPrice(),
                    $fulfillment->getOrder()->getBrand()
                ];
            }

            $filePath = sys_get_temp_dir()."/shippers-export-".time().".csv";

            $f = fopen($filePath, "w");

            fputcsv(
                $f,
                [
                    'Order ID',
                    'Ordered On (PST)',
                    'Email',
                    'Company',
                    'Phone',
                    'First Name',
                    'Last Name',
                    'Street Line 1',
                    'Street Line 2',
                    'City',
                    'State/Province',
                    'Zip/Postal Code',
                    'Country',
                    'Is Fulfilled',
                    'Order Item ID',
                    'Product ID',
                    'Product Name',
                    'Product Inventory SKU',
                    'Product Fulfillment/Inventory Name',
                    'Product Marketing SKU',
                    'Product Weight *',
                    'Quantity',
                    'Item Total $',
                    'Fulfillment Status',
                    'Shipping Company',
                    'Tracking Number',
                    'Fulfilled On',
                    'Order Total $',
                    'Brand',
                ]
            );

            foreach ($rows as $line) {
                fputcsv($f, $line);
            }

            return response()
                ->download($filePath)
                ->deleteFileAfterSend();
        }

        return ResponseService::fulfillment(
                $fulfillmentsAndBuilder->getResults(),
                $fulfillmentsAndBuilder->getQueryBuilder()
            )
            ->respond(200);
    }

    /**
     * Fulfilled order or order item. If the order_item_id it's set on the request only the order item it's fulfilled,
     * otherwise entire order it's fulfilled.
     *
     * @param  OrderFulfilledRequest  $request
     *
     * @throws Throwable
     * @return JsonResponse
     *
     */
    public function markShippingFulfilled(OrderFulfilledRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'fulfilled.fulfillment');

        $fulfillments = $this->orderItemFulfillmentRepository->getByOrderAndOrderItem(
            $request->get('order_id'),
            $request->get('order_item_id')
        );

        $found = false;

        foreach ($fulfillments as $fulfillment) {
            /**
             * @var $fulfillment OrderItemFulfillment
             */
            $found = true;

            $fulfillment->setStatus(config('ecommerce.fulfillment_status_fulfilled'));
            $fulfillment->setCompany($request->get('shipping_company'));
            $fulfillment->setTrackingNumber($request->get('tracking_number'));
            $fulfillment->setFulfilledOn(
                Carbon::parse(
                    $request->get(
                        'fulfilled_on',
                        Carbon::now()
                            ->toDateTimeString()
                    )
                )
            );
        }

        $this->entityManager->flush();

        throw_if(
            ! $found,
            new NotFoundException('Fulfilled failed.')
        );

        return ResponseService::empty(201);
    }

    /**
     * Delete order or order item fulfillment.
     *
     * @param $orderId
     * @param  null  $orderItemId
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws NotAllowedException
     *
     * @return JsonResponse
     *
     */
    public function delete($orderId, $orderItemId = null)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'delete.fulfillment');

        $fulfillments = $this->orderItemFulfillmentRepository->getByOrderAndOrderItem(
            $orderId,
            $orderItemId
        );

        if (empty($fulfillments)) {
            return ResponseService::empty(422);
        }

        foreach ($fulfillments as $fulfillment) {
            $this->entityManager->remove($fulfillment);
        }

        $this->entityManager->flush();

        return ResponseService::empty(204);
    }
}
