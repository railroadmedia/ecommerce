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

    public function handleCreated(Created $event)
    {
        if ($event->class == OrderItemRepository::class) {
            $order = $this->orderRepository->read($event->attributes['order_id']);
            if ($order['user_id'] && $event->entity['product']['type'] == ConfigService::$typeProduct) {
                $this->userProductService->assignUserProduct(
                    $order['user_id'],
                    $event->attributes['product_id'],
                    null,
                    $event->attributes['quantity']
                );
            }
        }
        if ($event->class == SubscriptionRepository::class) {
            $products = [];
            if ($event->entity['type'] == ConfigService::$paymentPlanType) {
                $order = $this->orderRepository->read($event->entity['order_id']);
                $products = $order['items'];
            } else {
                $products[] = [
                    'product_id' => $event->entity['product_id'],
                    'quantity' => 1,
                ];
            }

            foreach ($products as $product) {
                $this->userProductService->assignUserProduct(
                    $event->attributes['user_id'],
                    $product['product_id'],
                    $event->attributes['paid_until'],
                    $product['quantity']
                );
            }
        }
        if ($event->class == RefundRepository::class) {
            //the payment it's fully refunded
            if ($event->attributes['payment_amount'] == $event->attributes['refunded_amount']) {
                $payment = $this->paymentRepository->read($event->attributes['payment_id']);
                $products = [];
                if ($payment['order']) {
                    $products = $payment['order']['items'];
                } else {
                    if ($payment['subscription']) {
                        if ($payment['subscription']['type'] == ConfigService::$paymentPlanType) {
                            $order = $this->orderRepository->read($payment['subscription']['order_id']);
                            $products = $order['items'];
                        } else {
                            $products[] = [
                                'product_id' => $payment['subscription']['product_id'],
                            ];
                        }
                    }
                }
                $products = array_pluck($payment['order']['items'], 'quantity', 'product_id');
                $this->userProductService->removeUserProducts($payment['user']->user_id, $products);
            }
        }
    }

    public function handleUpdated(Updated $event)
    {
        if ($event->class == SubscriptionRepository::class) {
            $products = [];
            if ($event->entity['type'] == ConfigService::$paymentPlanType) {
                $order = $this->orderRepository->read($event->entity['order_id']);
                $products = $order['items'];
            } else {
                $products[] = [
                    'product_id' => $event->entity['product_id'],
                ];
            }
            if (!$event->attributes['canceled_on'] && $event->entity['is_active']) {
                foreach ($products as $product) {
                    $userProduct = ($this->userProductService->getUserProductData(
                        $event->entity['user_id'],
                        $product['product_id']
                    ));

                    $this->userProductService->updateUserProduct(
                        $userProduct['id'],
                        $userProduct['quantity'],
                        $event->attributes['paid_until']
                    );
                }
            } else {
                $products = array_pluck($products, 'quantity', 'product_id');
                $this->userProductService->removeUserProducts($event->entity['user_id'], $products);
            }
        }
    }
}