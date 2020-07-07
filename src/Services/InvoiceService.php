<?php

namespace Railroad\Ecommerce\Services;

use Exception;
use Illuminate\Support\Facades\Mail;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Mail\OrderInvoice;
use Railroad\Ecommerce\Mail\SubscriptionInvoice;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;

class InvoiceService
{
    /**
     * @var TaxService
     */
    private $taxService;

    /**
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * InvoiceService constructor.
     * @param TaxService $taxService
     * @param SubscriptionRepository $subscriptionRepository
     */
    public function __construct(TaxService $taxService, SubscriptionRepository $subscriptionRepository)
    {
        $this->taxService = $taxService;
        $this->subscriptionRepository = $subscriptionRepository;
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

        $addressToUseForTax =
            !empty($order->getShippingAddress()) ? $order->getShippingAddress() : $order->getBillingAddress();

        $taxesPerType = $this->taxService->getTaxesDuePerType(
            $order->getProductDue(),
            $order->getShippingDue(),
            !empty($addressToUseForTax) ? $addressToUseForTax->toStructure() : null
        );

        $paymentPlan = $this->subscriptionRepository->findOneBy(['order' => $order]);

        return [
            'order' => $order,
            'subscription' => $paymentPlan,
            'paymentPlan' => $paymentPlan,
            'orderItems' => $order->getOrderItems(),
            'payment' => $payment,
            'currencySymbol' => $currencySymbol,
            'taxesPerType' => $taxesPerType,
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

        $taxesPerType = $this->taxService->getTaxesDuePerType(
            $subscription->getTotalPrice(),
            0,
            (!empty($subscription->getPaymentMethod()) &&
                !empty($subscription->getPaymentMethod()->getBillingAddress())) ? $subscription->getPaymentMethod()
                ->getBillingAddress()
                ->toStructure() : null
        );

        if ($subscription->getType() == Subscription::TYPE_PAYMENT_PLAN) {
            return $this->getViewDataForOrderInvoice($subscription->getOrder(), $payment);
        }

        return [
            'subscription' => $subscription,
            'order' => $subscription->getOrder(),
            'orderItems' => $subscription->getOrder()->getOrderItems(),
            'product' => $subscription->getProduct(),
            'payment' => $payment,
            'currencySymbol' => $currencySymbol,
            'taxesPerType' => $taxesPerType,
            'invoiceSenderEmail' => config(
                'ecommerce.invoice_email_details.' . $payment->getGatewayName() . '.order_invoice.invoice_sender'
            ),
            'invoiceSenderAddress' => config(
                'ecommerce.invoice_email_details.' . $payment->getGatewayName() . '.order_invoice.invoice_address'
            ),
        ];
    }

    /**
     * @param Subscription $subscription
     * @param Payment $payment
     */
    public function sendSubscriptionRenewalInvoiceEmail(Subscription $subscription, Payment $payment)
    {
        try {
            // only try and send the email if its configured
            if (empty(
            config(
                'ecommerce.invoice_email_details.' .
                $payment->getGatewayName() .
                '.subscription_renewal_invoice.invoice_sender'
            )
            )) {
                return;
            }

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