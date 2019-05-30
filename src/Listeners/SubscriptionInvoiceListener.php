<?php

namespace Railroad\Ecommerce\Listeners;

use Railroad\Ecommerce\Events\Subscriptions\SubscriptionRenewed;
use Railroad\Ecommerce\Services\InvoiceService;

class SubscriptionInvoiceListener
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
     * @param SubscriptionRenewed $subscriptionRenewed
     */
    public function handle(SubscriptionRenewed $subscriptionRenewed)
    {
        $this->invoiceService->sendSubscriptionRenewalInvoiceEmail(
            $subscriptionRenewed->getSubscription(),
            $subscriptionRenewed->getPayment()
        );
    }
}
