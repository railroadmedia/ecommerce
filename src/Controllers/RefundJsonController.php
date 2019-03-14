<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\Refund;
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
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Ecommerce\Services\UserProductService;
use Railroad\Permissions\Services\PermissionService;
use Spatie\Fractal\Fractal;
use Throwable;

class RefundJsonController extends BaseController
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
     * @var \Railroad\Permissions\Services\PermissionService
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
    ) {
        parent::__construct();

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

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->paymentRepository->createQueryBuilder('p');

        $qb
            ->select(['p', 'pm'])
            ->join('p.paymentMethod', 'pm')
            ->where($qb->expr()->eq('p.id', ':id'))
            ->setParameter('id', $paymentId);

        /**
         * @var $payment \Railroad\Ecommerce\Entities\Payment
         */
        $payment = $qb->getQuery()->getOneOrNullResult();

        /**
         * @var $paymentMethod \Railroad\Ecommerce\Entities\PaymentMethod
         */
        $paymentMethod = $payment->getPaymentMethod();

        $refundExternalId = null;

        if ($paymentMethod->getMethodType() == PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE) {
            $refundExternalId = $this->stripePaymentGateway->refund(
                $request->input('data.attributes.gateway_name'),
                $request->input('data.attributes.refund_amount'),
                $payment->getExternalId(),
                $request->input('data.attributes.note')
            );
        } else if ($paymentMethod->getMethodType() == PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE) {
            $refundExternalId = $this->payPalPaymentGateway->refund(
                $request->input('data.attributes.refund_amount'),
                $payment->getCurrency(),
                $payment->getExternalId(),
                $request->input('data.attributes.gateway_name'),
                $request->input('data.attributes.note')
            );
        }

        $refund = new Refund();

        $refund
            ->setPayment($payment)
            ->setPaymentAmount($payment->getTotalDue())
            ->setRefundedAmount($request->input('data.attributes.refund_amount'))
            ->setNote($request->input('data.attributes.note'))
            ->setExternalId($refundExternalId)
            ->setExternalProvider($payment->getExternalProvider())
            ->setCreatedAt(Carbon::now());

        $this->entityManager->persist($refund);

        $payment->setTotalRefunded(
            $payment->getTotalRefunded() + $refund->getRefundedAmount()
        );

        // cancel shipping fulfillment

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->orderPaymentRepository->createQueryBuilder('op');

        $qb
            ->select(['op', 'p'])
            ->join('op.payment', 'p')
            ->where($qb->expr()->eq('op.payment', ':payment'))
            ->andWhere($qb->expr()->isNull('p.deletedOn'))
            ->setParameter('payment', $payment);

        $orderPayments = $qb->getQuery()->getResult();

        $distinctOrders = [];

        /**
         * @var $orderPayment \Railroad\Ecommerce\Entities\OrderPayment
         */
        foreach ($orderPayments as $orderPayment) {
            /**
             * @var $order \Railroad\Ecommerce\Entities\Order
             */
            $order = $orderPayment->getOrder();
            $distinctOrders[$order->getId()] = $order;
        }

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->orderItemFulfillmentRepository->createQueryBuilder('oif');

        $qb
            ->where($qb->expr()->in('oif.order', ':orders'))
            ->andWhere($qb->expr()->eq('oif.status', ':status'))
            ->andWhere($qb->expr()->isNull('oif.fulfilledOn'))
            ->setParameter('orders', array_values($distinctOrders))
            ->setParameter('status', ConfigService::$fulfillmentStatusPending);

        $orderItemFulfillments = $qb->getQuery()->getResult();

        foreach ($orderItemFulfillments as $orderItemFulfillment) {
            $this->entityManager->remove($orderItemFulfillment);
        }

        // remove user products if full refund
        if ($refund->getPaymentAmount() == $refund->getRefundedAmount()) {
            if (count($orderPayments)) {
                /**
                 * @var $qb \Doctrine\ORM\QueryBuilder
                 */
                $qb = $this->orderItemRepository->createQueryBuilder('oi');

                $qb
                    ->select(['oi', 'p'])
                    ->join('oi.product', 'p')
                    ->where($qb->expr()->in('oi.order', ':orders'))
                    ->setParameter('orders', array_values($distinctOrders));

                $orderItems = $qb->getQuery()->getResult();

                $distinctProducts = [];

                /**
                 * @var $orderItem \Railroad\Ecommerce\Entities\OrderItem
                 */
                foreach ($orderItems as $orderItem) {
                    /**
                     * @var $product \Railroad\Ecommerce\Entities\Product
                     */
                    $product = $orderItem->getProduct();

                    $distinctProducts[$product->getId()] = $product;
                }

                /**
                 * @var $qb \Doctrine\ORM\QueryBuilder
                 */
                $qb = $this->userProductRepository->createQueryBuilder('up');

                $qb
                    ->where($qb->expr()->in('up.product', ':products'))
                    ->setParameter('products', array_values($distinctProducts));

                $userProducts = $qb->getQuery()->getResult();

                foreach ($userProducts as $userProduct) {
                    $this->entityManager->remove($userProduct);
                }
            } else {
                /**
                 * @var $qb \Doctrine\ORM\QueryBuilder
                 */
                $qb = $this->subscriptionPaymentRepository
                                ->createQueryBuilder('sp');

                $qb
                    ->select(['sp', 's'])
                    ->join('sp.subscription', 's')
                    ->where($qb->expr()->eq('sp.payment', ':payment'))
                    ->setParameter('payment', $payment);

                $subscriptionPayments = $qb->getQuery()->getResult();

                /**
                 * @var $subscriptionPayment \Railroad\Ecommerce\Entities\SubscriptionPayment
                 */
                foreach ($subscriptionPayments as $subscriptionPayment) {
                    /**
                     * @var $subscription \Railroad\Ecommerce\Entities\Subscription
                     */
                    $subscription = $subscriptionPayment->getSubscription();

                    $subscriptionProducts = $this->userProductService
                            ->getSubscriptionProducts($subscription);

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