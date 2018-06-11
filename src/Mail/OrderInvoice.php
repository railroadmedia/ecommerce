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
     *  Create a new message instance.
     *
     * @param array $viewData
     */
    public function __construct(array $viewData)
    {
        $this->viewData = $viewData;
    }

    /**
     * Build the message.
     *
     * @return \Railroad\Ecommerce\Mail\OrderInvoice
     */
    public function build()
    {
        return $this
            ->from(config('ecommerce.invoiceSender'), config('ecommerce.invoiceSenderName'))
            ->subject(config('ecommerce.invoiceEmailSubject'))
            ->view('ecommerce::billing', $this->viewData);
    }
}