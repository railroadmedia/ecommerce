<?php

namespace Railroad\Ecommerce\Mail;

use Illuminate\Mail\Mailable;

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
            ->view('ecommerce::subscription_renewal_invoice', $this->viewData);
    }
}