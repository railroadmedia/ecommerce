<?php

namespace Railroad\Ecommerce\Listeners;

use Railroad\Ecommerce\Events\Subscriptions\MobileSubscriptionRenewed;
use Railroad\Ecommerce\Services\InvoiceService;

class MobileSubscriptionInvoiceListener
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
     * @param MobileSubscriptionRenewed $mobileSubscriptionRenewed
     */
    public function handle(MobileSubscriptionRenewed $mobileSubscriptionRenewed)
    {
        $this->invoiceService->sendSubscriptionRenewalInvoiceEmail(
            $mobileSubscriptionRenewed->getSubscription(),
            $mobileSubscriptionRenewed->getPayment()
        );
    }
}
