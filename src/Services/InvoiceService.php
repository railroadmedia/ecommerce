<?php

namespace Railroad\Ecommerce\Services;

use Exception;
use Illuminate\Support\Facades\Mail;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Mail\OrderInvoice;
use Railroad\Ecommerce\Mail\SubscriptionInvoice;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

class InvoiceService
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
     * InvoiceService constructor.
     * @param TaxService $taxService
     */
    public function __construct(TaxService $taxService)
    {
        $this->taxService = $taxService;
    }

    /**
     * @param Order $order
     * @param Payment $payment
     * @return array
     * @throws Exception
     */
    public function getViewDataForOrderInvoice(Order $order, Payment $payment)
    {
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

        if (!empty($order->getShippingAddress()) && !empty(
            $order->getShippingAddress()
                ->getCountry()
            )) {

            $gstRate = $this->taxService->getGSTTaxRate(
                $order->getShippingAddress()
                    ->toStructure()
            );
        }
        elseif (!empty($order->getBillingAddress()) && !empty(
            $order->getBillingAddress()
                ->getCountry()
            )) {

            $gstRate = $this->taxService->getGSTTaxRate(
                $order->getBillingAddress()
                    ->toStructure()
            );
        }
        else {
            $gstRate = 0;
        }

        if ($gstRate > 0) {
            $gstPaid = round(($order->getProductDue() + $order->getShippingDue()) * $gstRate, 2);
        }
        else {
            $gstPaid = 0;
        }

        return [
            'order' => $order,
            'orderItems' => $order->getOrderItems(),
            'payment' => $payment,
            'currencySymbol' => $currencySymbol,
            'gstPaid' => $gstPaid,
            'invoiceSenderEmail' => config(
                'ecommerce.invoice_email_details.' . $payment->getGatewayName() . '.order_invoice.invoice_sender'
            ),
            'invoiceSenderAddress' => config(
                'ecommerce.invoice_email_details.' . $payment->getGatewayName() . '.order_invoice.invoice_address'
            ),
        ];
    }

    /**
     * @param Order $order
     * @param Payment $payment
     * @throws Exception
     */
    public function sendOrderInvoiceEmail(Order $order, Payment $payment)
    {
        try {
            $orderInvoiceEmail = new OrderInvoice(
                $this->getViewDataForOrderInvoice($order, $payment), $payment->getGatewayName()
            );

            $emailAddress =
                $order->getUser() ?
                    $order->getUser()
                        ->getEmail() :
                    $order->getCustomer()
                        ->getEmail();

            Mail::to($emailAddress)
                ->send($orderInvoiceEmail);

        } catch (Exception $e) {
            error_log('Failed to send invoice for order: ' . $order->getId());
            error_log($e);
        }
    }

    /**
     * @param Subscription $subscription
     * @param Payment $payment
     * @return array
     * @throws Exception
     */
    public function getViewDataForSubscriptionRenewalInvoice(Subscription $subscription, Payment $payment)
    {
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

        if ($subscription->getPaymentMethod() &&
            !empty(
                $subscription->getPaymentMethod()
                    ->getBillingAddress()
            ) && !empty(
            $subscription->getPaymentMethod()
                ->getBillingAddress()
                ->getCountry()
            )) {

            $gstRate = $this->taxService->getGSTTaxRate(
                $subscription->getPaymentMethod()
                    ->getBillingAddress()
                    ->toStructure()
            );
        }
        else {
            $gstRate = 0;
        }

        if ($gstRate > 0) {
            $gstPaid = round($payment->getTotalPaid() * $gstRate, 2);
        }
        else {
            $gstPaid = 0;
        }

        return [
            'subscription' => $subscription,
            'product' => $subscription->getProduct(),
            'payment' => $payment,
            'currencySymbol' => $currencySymbol,
            'gstPaid' => $gstPaid,
            'invoiceSenderEmail' => config(
                'ecommerce.invoice_email_details.' . $payment->getGatewayName() . '.order_invoice.invoice_sender'
            ),
            'invoiceSenderAddress' => config(
                'ecommerce.invoice_email_details.' . $payment->getGatewayName() . '.order_invoice.invoice_address'
            ),
        ];
    }

    public function sendSubscriptionRenewalInvoiceEmail(Subscription $subscription, Payment $payment)
    {
        try {
            $subscriptionRenewalInvoiceEmail = new SubscriptionInvoice(
                $this->getViewDataForSubscriptionRenewalInvoice($subscription, $payment), $payment->getGatewayName()
            );

            $emailAddress =
                $subscription->getUser()
                    ->getEmail();

            Mail::to($emailAddress)
                ->send($subscriptionRenewalInvoiceEmail);

        } catch (Exception $e) {
            error_log('Failed to send invoice for subscription renewal: ' . $subscription->getId());
            error_log($e);
        }
    }
}