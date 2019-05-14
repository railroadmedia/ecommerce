<?php

namespace Railroad\Ecommerce\Mail;

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
     * @return \Railroad\Ecommerce\Mail\OrderInvoice
     */
    public function build()
    {
        return $this->from(
            config('ecommerce.invoice_gateway_details.pianote.invoice_sender'),
            config('ecommerce.invoice_gateway_details.pianote.invoice_sender_name')
        )
            ->subject(config('ecommerce.invoice_gateway_details.pianote.invoice_email_subject'))
            ->view('ecommerce::billing', $this->viewData);
    }
}