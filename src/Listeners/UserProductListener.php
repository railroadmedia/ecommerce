<?php

namespace Railroad\Ecommerce\Listeners;

use Railroad\Ecommerce\Repositories\OrderItemRepository;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\RefundRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\UserProductService;
use Railroad\Resora\Events\Created;
use Railroad\Resora\Events\Updated;

class UserProductListener
{
    /**
     * @var UserProductService
     */
    protected $userProductService;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;

    /**
     * UserProductListener constructor.
     *
     * @param UserProductService $userProductService
     * @param OrderRepository $orderRepository
     * @param PaymentRepository $paymentRepository
     */
    public function __construct(
        UserProductService $userProductService,
        OrderRepository $orderRepository,
        PaymentRepository $paymentRepository
    ) {
        $this->userProductService = $userProductService;
        $this->orderRepository = $orderRepository;
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * @param Created $event
     */
    public function handleCreated(Created $event)
    {
        if ($event->class === OrderItemRepository::class) {
            $order = $this->orderRepository->read($event->attributes['order_id']);

            if ($order['user_id'] && $event->entity['product']['type'] == ConfigService::$typeProduct) {
                $this->assignUserProduct(
                    $order['user_id'],
                    $event->attributes['product_id'],
                    null,
                    $event->attributes['quantity']
                );
            }
        }

        if ($event->class === SubscriptionRepository::class) {
            if ($event->entity['type'] == ConfigService::$typeSubscription) {
                $products = $this->getProducts(
                    $event->entity['type'],
                    $event->entity['order_id'],
                    $event->entity['product_id']
                );

                foreach ($products as $product) {
                    $this->assignUserProduct(
                        $event->attributes['user_id'],
                        $product['product_id'],
                        $event->attributes['paid_until'],
                        $product['quantity']
                    );
                }
            }
        }

        if ($event->class === RefundRepository::class) {

            //the payment it's fully refunded
            if ($event->attributes['payment_amount'] == $event->attributes['refunded_amount']) {
                $payment = $this->paymentRepository->read($event->attributes['payment_id']);
                $products = [];

                if ($payment['order']) {
                    $products = $payment['order']['items'] ?? [];
                } else {
                    if ($payment['subscription']) {
                        $products = $this->getProducts(
                            $payment['subscription']['type'],
                            $payment['subscription']['order_id'],
                            $payment['subscription']['product_id']
                        );
                    }
                }
                $this->removeUserProducts($payment['user']->user_id, $products);
            }
        }
    }

    /**
     * @param Updated $event
     */
    public function handleUpdated(Updated $event)
    {
        if ($event->class === SubscriptionRepository::class) {

            $products =
                $this->getProducts($event->entity['type'], $event->entity['order_id'], $event->entity['product_id']);

            if (!$event->attributes['canceled_on'] &&
                $event->entity['is_active'] &&
                $event->entity['type'] == ConfigService::$tableSubscription) {

                foreach ($products as $product) {
                    $this->assignUserProduct(
                        $event->entity['user_id'],
                        $product['product_id'],
                        $event->attributes['paid_until']
                    );
                }
            } else {
                $this->removeUserProducts($event->entity['user_id'], $products);
            }
        }
    }

    /**
     * @param $subscriptionType
     * @param $orderId
     * @param $productId
     * @return array|mixed
     */
    private function getProducts($subscriptionType, $orderId, $productId)
    {
        $products = [];
        if ($subscriptionType == ConfigService::$paymentPlanType) {
            $order = $this->orderRepository->read($orderId);
            $products = $order['items'] ?? [];
        } else {
            $products[] = [
                'product_id' => $productId,
                'quantity' => 1,
            ];
        }
        return $products;
    }

    /**
     * @param $userId
     * @param $productId
     * @param $expirationDate
     * @param int $quantity
     */
    private function assignUserProduct($userId, $productId, $expirationDate, $quantity = 0)
    {
        $this->userProductService->assignUserProduct(
            $userId,
            $productId,
            $expirationDate,
            $quantity
        );
    }

    /**
     * @param $userId
     * @param $products
     */
    private function removeUserProducts($userId, $products)
    {
        $products = array_pluck($products, 'quantity', 'product_id');
        $this->userProductService->removeUserProducts($userId, $products);
    }
}