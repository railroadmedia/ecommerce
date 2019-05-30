<?php

namespace Railroad\Ecommerce\Mail;

use Exception;
use Illuminate\Mail\Mailable;

class OrderInvoice extends Mailable
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
     * @return OrderInvoice
     * @throws Exception
     */
    public function build()
    {
        if (empty(config('ecommerce.invoice_email_details.' . $this->gateway . '.order_invoice.invoice_sender'))) {
            throw new Exception(
                'Could not build order invoice email, configuration not set for gateway: ' . $this->gateway
            );
        }

        return $this->from(
            config('ecommerce.invoice_email_details.' . $this->gateway . '.order_invoice.invoice_sender'),
            config('ecommerce.invoice_email_details.' . $this->gateway . '.order_invoice.invoice_sender_name')
        )
            ->subject(config('ecommerce.invoice_email_details.' . $this->gateway . '.order_invoice.invoice_email_subject'))
            ->view(config('ecommerce.invoice_email_details.' . $this->gateway . '.order_invoice.invoice_view'), $this->viewData);
    }
}