<?php

namespace Railroad\Ecommerce\Listeners;

use Exception;
use Illuminate\Support\Facades\Mail;
use Railroad\Ecommerce\Events\OrderEvent;
use Railroad\Ecommerce\Mail\OrderInvoice;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Services\TaxService;

class OrderInvoiceListener
{
    /**
     * @var EcommerceEntityManager
     */
    protected $entityManager;

    /**
     * @var TaxService
     */
    private $taxService;

    /**
     * @param EcommerceEntityManager $entityManager
     */
    public function __construct(EcommerceEntityManager $entityManager, TaxService $taxService)
    {
        $this->entityManager = $entityManager;
        $this->taxService = $taxService;
    }

    /**
     * @param OrderEvent $event
     * @throws Exception
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

        if (!empty($order->getShippingAddress()) &&
            !empty(
            $order->getShippingAddress()
                ->getCountry()
            )) {

            $gstRate =
                $this->taxService->getGSTTaxRate(
                    $order->getShippingAddress()
                        ->toStructure()
                );
        }
        elseif (!empty($order->getBillingAddress()) &&
            !empty(
            $order->getBillingAddress()
                ->getCountry()
            )) {

            $gstRate =
                $this->taxService->getGSTTaxRate(
                    $order->getBillingAddress()
                        ->toStructure()
                );
        }
        else {
            $gstRate = 0;
        }

        if ($gstRate > 0) {
            $gstPaid = round(($order->getProductDue() + $order->getShippingDue()) * $gstRate, 2);
        } else {
            $gstPaid = 0;
        }

        $orderInvoiceEmail = new OrderInvoice(
            [
                'order' => $order,
                'orderItems' => $order->getOrderItems(),
                'payment' => $payment,
                'currencySymbol' => $currencySymbol,
                'gstPaid' => $gstPaid
            ], $payment->getGatewayName()
        );

        $emailAddress =
            $order->getUser() ?
                $order->getUser()
                    ->getEmail() :
                $order->getCustomer()
                    ->getEmail();

        try {
            Mail::to($emailAddress)
                ->send($orderInvoiceEmail);
        } catch (Exception $e) {
            error_log('Failed to send invoice for order: ' . $order->getId());
        }
    }
}
