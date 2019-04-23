<?php

namespace Railroad\Ecommerce\Listeners;

use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Railroad\Ecommerce\Events\OrderEvent;
use Railroad\Ecommerce\Mail\OrderInvoice;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Services\ConfigService;

class OrderInvoiceListener
{
    /**
     * @var EcommerceEntityManager
     */
    protected $entityManager;

    /**
     * @param EcommerceEntityManager $entityManager
     */
    public function __construct(EcommerceEntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param OrderEvent $event
     */
    public function handle(OrderEvent $event)
    {
        $order = $event->getOrder();
        $payment = $event->getPayment();

        switch ($payment->getCurrency()) {
            case 'USD':
            case 'CAD':
            default:
                $currencySymbol = '$';
                break;
            case 'GBP':
                $currencySymbol = '£';
                break;
            case 'EUR':
                $currencySymbol = '€';
                break;
        }

        $orderInvoiceEmail = new OrderInvoice(
            [
                'order' => $order,
                'orderItems' => $order->getOrderItems(),
                'payment' => $payment,
                'currencySymbol' => $currencySymbol,
            ]
        );

        $emailAddress = $order->getUser() ? $order->getUser()->getEmail() : $order->getCustomer()->getEmail();

        try {
            Mail::to($emailAddress)
                ->send($orderInvoiceEmail);
        } catch (Exception $e) {
            error_log('Failed to send invoice for order: ' . $order->getId());
        }
    }
}
