<?php

namespace Railroad\Ecommerce\Listeners;

use Exception;
use Railroad\Ecommerce\Events\OrderEvent;
use Railroad\Ecommerce\Services\InvoiceService;

class OrderInvoiceListener
{
    /**
     * @var InvoiceService
     */
    private $invoiceService;

    /**
     * @param InvoiceService $invoiceService
     */
    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * @param OrderEvent $event
     * @throws Exception
     */
    public function handle(OrderEvent $event)
    {
        $this->invoiceService->sendOrderInvoiceEmail($event->getOrder(), $event->getPayment());
    }
}
