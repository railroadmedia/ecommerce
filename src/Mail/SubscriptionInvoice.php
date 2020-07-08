<?php

namespace Railroad\Ecommerce\Mail;

use Illuminate\Mail\Mailable;
use Railroad\Ecommerce\Entities\Subscription;

class SubscriptionInvoice extends Mailable
{
    /**
     * @var array
     */
    public $viewData = [];

    /**
     * @var
     */
    private $gateway;

    /**
     *  Create a new message instance.
     *
     * @param array $viewData
     * @param $gateway
     */
    public function __construct(array $viewData, $gateway)
    {
        $this->viewData = $viewData;
        $this->gateway = $gateway;
    }

    /**
     * Build the message.
     *
     * @return SubscriptionInvoice
     */
    public function build()
    {
        $view = 'ecommerce::subscription_renewal_invoice';

        // if its a payment plan we'll use the order invoice view
        if (!empty($this->viewData['subscription']) &&
            $this->viewData['subscription']->getType() == Subscription::TYPE_PAYMENT_PLAN) {
            $view = 'ecommerce::order_invoice';
        }

        return $this->from(
            config(
                'ecommerce.invoice_email_details.' . $this->gateway . '.subscription_renewal_invoice.invoice_sender'
            ),
            config(
                'ecommerce.invoice_email_details.' .
                $this->gateway .
                '.subscription_renewal_invoice.invoice_sender_name'
            )
        )
            ->subject(
                config(
                    'ecommerce.invoice_email_details.' .
                    $this->gateway .
                    '.subscription_renewal_invoice.invoice_email_subject'
                )
            )
            ->view($view, $this->viewData);
    }
}