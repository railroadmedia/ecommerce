<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\OrderItem;
use Railroad\Ecommerce\Entities\OrderPayment;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Refund;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\SubscriptionPayment;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\OrderItemFulfillmentRepository;
use Railroad\Ecommerce\Repositories\OrderItemRepository;
use Railroad\Ecommerce\Repositories\OrderPaymentRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository;
use Railroad\Ecommerce\Repositories\UserProductRepository;
use Railroad\Ecommerce\Requests\RefundCreateRequest;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Ecommerce\Services\UserProductService;
use Railroad\Permissions\Services\PermissionService;
use Spatie\Fractal\Fractal;
use Throwable;

class RefundJsonController extends Controller
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
     * @var OrderItemRepository
     */
    private $orderItemRepository;

    /**
     * @var OrderPaymentRepository
     */
    private $orderPaymentRepository;

    /**
     * @var PaymentRepository
     */
    private $paymentRepository;

    /**
     * @var PayPalPaymentGateway
     */
    private $payPalPaymentGateway;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var StripePaymentGateway
     */
    private $stripePaymentGateway;

    /**
     * @var SubscriptionPaymentRepository
     */
    private $subscriptionPaymentRepository;

    /**
     * @var UserProductService
     */
    private $userProductService;

    /**
     * @var UserProductRepository
     */
    private $userProductRepository;

    /**
     * RefundJsonController constructor.
     *
     * @param EcommerceEntityManager $entityManager
     * @param OrderItemFulfillmentRepository $orderItemFulfillmentRepository
     * @param OrderItemRepository $orderItemRepository
     * @param OrderPaymentRepository $orderPaymentRepository
     * @param PaymentRepository $paymentRepository
     * @param PayPalPaymentGateway $payPalPaymentGateway
     * @param PermissionService $permissionService
     * @param StripePaymentGateway $stripePaymentGateway
     * @param SubscriptionPaymentRepository $subscriptionPaymentRepository
     * @param UserProductService $userProductService
     * @param UserProductRepository $userProductRepository
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        OrderItemFulfillmentRepository $orderItemFulfillmentRepository,
        OrderItemRepository $orderItemRepository,
        OrderPaymentRepository $orderPaymentRepository,
        PaymentRepository $paymentRepository,
        PayPalPaymentGateway $payPalPaymentGateway,
        PermissionService $permissionService,
        StripePaymentGateway $stripePaymentGateway,
        SubscriptionPaymentRepository $subscriptionPaymentRepository,
        UserProductService $userProductService,
        UserProductRepository $userProductRepository
    )
    {
        $this->entityManager = $entityManager;
        $this->orderItemFulfillmentRepository = $orderItemFulfillmentRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->paymentRepository = $paymentRepository;
        $this->payPalPaymentGateway = $payPalPaymentGateway;
        $this->permissionService = $permissionService;
        $this->stripePaymentGateway = $stripePaymentGateway;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->userProductService = $userProductService;
        $this->userProductRepository = $userProductRepository;
    }

    /**
     * Call the refund method from the external payment helper and the method that save the refund in the database.
     * Return the new created refund in JSON format
     *
     * @param RefundCreateRequest $request
     *
     * @return Fractal
     *
     * @throws Throwable
     */
    public function store(RefundCreateRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'store.refund');

        $paymentId = $request->input('data.relationships.payment.data.id');

        $payment = $this->paymentRepository->getPaymentAndPaymentMethod($paymentId);

        /**
         * @var $paymentMethod PaymentMethod
         */
        $paymentMethod = $payment->getPaymentMethod();

        $refundExternalId = null;

        if ($paymentMethod->getMethodType() == PaymentMethod::TYPE_CREDIT_CARD) {
            $refundExternalId = $this->stripePaymentGateway->refund(
                $request->input('data.attributes.gateway_name'),
                $request->input('data.attributes.refund_amount'),
                $payment->getExternalId(),
                $request->input('data.attributes.note')
            );
        }
        else {
            if ($paymentMethod->getMethodType() == PaymentMethod::TYPE_PAYPAL) {
                $refundExternalId = $this->payPalPaymentGateway->refund(
                    $request->input('data.attributes.refund_amount'),
                    $payment->getCurrency(),
                    $payment->getExternalId(),
                    $request->input('data.attributes.gateway_name'),
                    $request->input('data.attributes.note')
                );
            }
        }

        $refund = new Refund();
        $refund->setPayment($payment);
        $refund->setPaymentAmount($payment->getTotalDue());
        $refund->setRefundedAmount($request->input('data.attributes.refund_amount'));
        $refund->setExternalId($refundExternalId);
        $refund->setExternalProvider($payment->getExternalProvider());
        $refund->setCreatedAt(Carbon::now());
        $refund->setNote($request->input('data.attributes.note'));

        $this->entityManager->persist($refund);

        $payment->setTotalRefunded(
            $payment->getTotalRefunded() + $refund->getRefundedAmount()
        );

        // cancel shipping fulfillment

        $orderPayments = $this->orderPaymentRepository->getByPayment($payment);

        $distinctOrders = [];

        /**
         * @var $orderPayment OrderPayment
         */
        foreach ($orderPayments as $orderPayment) {
            /**
             * @var $order Order
             */
            $order = $orderPayment->getOrder();
            $distinctOrders[$order->getId()] = $order;
        }

        $orderItemFulfillments = $this->orderItemFulfillmentRepository
                                        ->getByOrders(array_values($distinctOrders));

        foreach ($orderItemFulfillments as $orderItemFulfillment) {
            $this->entityManager->remove($orderItemFulfillment);
        }

        // remove user products if full refund
        if ($refund->getPaymentAmount() == $refund->getRefundedAmount()) {
            if (count($orderPayments)) {

                $orderItems = $this->orderItemRepository->getByOrders(array_values($distinctOrders));

                $distinctProducts = [];
                $productsToUsersMap = [];
                /*
                // structure example
                $productsToUsersMap = [
                    product_id_1 => [user_id_1, user_id_2, user_id_3],
                    product_id_2 => [user_id_1, user_id_4, user_id_5]
                ];
                */

                /**
                 * @var $orderItem OrderItem
                 */
                foreach ($orderItems as $orderItem) {
                    /**
                     * @var $product Product
                     */
                    $product = $orderItem->getProduct();

                    $distinctProducts[$product->getId()] = $product;

                    if ($orderItem->getOrder() && $orderItem->getOrder()->getUser() &&
                        $orderItem->getOrder()->getUser()->getId()) {

                        if (!isset($productsToUsersMap[$product->getId()])) {
                            $productsToUsersMap[$product->getId()] = [];
                        }

                        $productsToUsersMap[$product->getId()][] = $orderItem->getOrder()->getUser()->getId();
                    }
                }

                $userProducts = $this->userProductRepository->getByProducts(array_values($distinctProducts));

                foreach ($userProducts as $userProduct) {
                    $productId = $userProduct->getProduct()->getId();
                    $userId = $userProduct->getUser()->getId();

                    if (isset($productsToUsersMap[$productId]) && in_array($userId, $productsToUsersMap[$productId])) {
                        $this->entityManager->remove($userProduct);
                    }
                }
            }
            else {

                $subscriptionPayments = $this->subscriptionPaymentRepository->getByPayment($payment);

                /**
                 * @var $subscriptionPayment SubscriptionPayment
                 */
                foreach ($subscriptionPayments as $subscriptionPayment) {
                    /**
                     * @var $subscription Subscription
                     */
                    $subscription = $subscriptionPayment->getSubscription();

                    $subscriptionProducts = $this->userProductService->getSubscriptionProducts($subscription);

                    $user = $subscription->getUser();

                    $this->userProductService->removeUserProducts(
                        $user,
                        $subscriptionProducts
                    );
                }
            }
        }

        $this->entityManager->flush();

        return ResponseService::refund($refund);
    }
}