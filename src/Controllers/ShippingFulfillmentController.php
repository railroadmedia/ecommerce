<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\OrderItemFulfillmentRepository;
use Railroad\Ecommerce\Requests\MarkFulfilledViaCSVUploadRequest;
use Railroad\Permissions\Services\PermissionService;
use Throwable;

class ShippingFulfillmentController extends Controller
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
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * ShippingFulfillmentJsonController constructor.
     *
     * @param EcommerceEntityManager $entityManager
     * @param OrderItemFulfillmentRepository $orderItemFulfillmentRepository
     * @param PermissionService $permissionService
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        OrderItemFulfillmentRepository $orderItemFulfillmentRepository,
        PermissionService $permissionService,
        UserProviderInterface $userProvider
    )
    {
        $this->entityManager = $entityManager;
        $this->orderItemFulfillmentRepository = $orderItemFulfillmentRepository;
        $this->permissionService = $permissionService;
        $this->userProvider = $userProvider;
    }

    /**
     * Pull shipping fulfillments. If the status it's set on the requests the results are filtered by status.
     *
     * @param MarkFulfilledViaCSVUploadRequest $request
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function markFulfilledViaCSVUploadShipstation(MarkFulfilledViaCSVUploadRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'upload.fulfillments');

        $errors = [];

        $filePath =
            $request->file('csv_file')
                ->getRealPath();

        $csvAsArray = array_map('str_getcsv', file($filePath));

        $orderIdColumnIndex = null;
        $shippingCompanyColumnIndex = null;
        $trackingNumberColumnIndex = null;
        $fulfilledOnColumnIndex = null;

        foreach ($csvAsArray[0] as $headerColumnIndex => $headerColumnValue) {
            if ($headerColumnValue == 'Order - Number') {
                $orderIdColumnIndex = $headerColumnIndex;
            }

            if ($headerColumnValue == 'Shipment - Service') {
                $shippingCompanyColumnIndex = $headerColumnIndex;
            }

            if ($headerColumnValue == 'Shipment - Tracking Number') {
                $trackingNumberColumnIndex = $headerColumnIndex;
            }

            if ($headerColumnValue == 'Date - Shipped Date') {
                $fulfilledOnColumnIndex = $headerColumnIndex;
            }
        }

        unset($csvAsArray[0]);
        $counter = 0;

        foreach ($csvAsArray as $csvRowIndex => $csvRow) {
            $counter++;

            if (empty($csvRow[$orderIdColumnIndex])) {
                $errors[] = 'Missing "Order - Number" at row: ' .
                    ($csvRowIndex + 1) .
                    ' column: ' .
                    ($orderIdColumnIndex + 1) .
                    '. Please review.';

                continue;
            }

            if (empty($csvRow[$shippingCompanyColumnIndex])) {
                $errors[] = 'Missing "Shipment - Service" name at row: ' .
                    ($csvRowIndex + 1) .
                    ' column: ' .
                    ($shippingCompanyColumnIndex + 1) .
                    '. Please review.';

                continue;
            }

            if (empty($csvRow[$trackingNumberColumnIndex])) {
                $errors[] = 'Missing "Shipment - Tracking Number" at row: ' .
                    ($csvRowIndex + 1) .
                    ' column: ' .
                    ($trackingNumberColumnIndex + 1) .
                    '. Please review.';

                continue;
            }

            if (empty($csvRow[$fulfilledOnColumnIndex])) {
                $errors[] = 'Missing "Date - Shipped Date" at row: ' .
                    ($csvRowIndex + 1) .
                    ' column: ' .
                    ($fulfilledOnColumnIndex + 1) .
                    '. Please review.';

                continue;
            }

            if (!is_numeric($csvRow[$orderIdColumnIndex])) {
                $errors[] = 'Invalid "Order - Number", skipping ' .
                    ($csvRowIndex + 1) .
                    ' column: ' .
                    ($fulfilledOnColumnIndex + 1) .
                    '.';

                continue;
            }

            $fulfillmentsForOrder = $this->orderItemFulfillmentRepository->getByOrderAndOrderItem(
                $csvRow[$orderIdColumnIndex]
            );

            if (empty($fulfillmentsForOrder)) {
                $errors[] =
                    'Could not find fulfillment with order ID ' . $csvRow[$orderIdColumnIndex] . '. Please review.';

                continue;
            }

            foreach ($fulfillmentsForOrder as $fulfillmentForOrder) {
                $fulfillmentForOrder->setStatus(config('ecommerce.fulfillment_status_fulfilled'));
                $fulfillmentForOrder->setCompany($csvRow[$shippingCompanyColumnIndex]);
                $fulfillmentForOrder->setTrackingNumber($csvRow[$trackingNumberColumnIndex]);
                $fulfillmentForOrder->setFulfilledOn(Carbon::parse($csvRow[$fulfilledOnColumnIndex]));

                $this->entityManager->persist($fulfillmentForOrder);
            }

            if ($counter % 100 == 0) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();

        return response()
            ->json(['success' => true, 'errors' => $errors])
            ->setStatusCode(count($errors) > 0 ? 422 : 201);
    }
}
