<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\Structures\AccountingProductTotals;

class AccountingProductsTotalsTransformer extends TransformerAbstract
{
    protected $defaultIncludes = [];

    public function transform(AccountingProductTotals $accountingProductTotals)
    {
        if (empty($accountingProductTotals->getAccountingProducts())) {
            $this->defaultIncludes = [];
        } else {
            $this->defaultIncludes = ['accountingProduct'];
        }

        return [
            'id' => $accountingProductTotals->getId(),
            'tax_paid' => $accountingProductTotals->getTaxPaid(),
            'shipping_paid' => $accountingProductTotals->getShippingPaid(),
            'finance_paid' => $accountingProductTotals->getFinancePaid(),
            'refunded' => $accountingProductTotals->getRefunded(),
            'net_product' => $accountingProductTotals->getNetProduct(),
            'net_paid' => $accountingProductTotals->getNetPaid(),
        ];
    }

    public function includeAccountingProduct(AccountingProductTotals $accountingProductTotals)
    {
        return $this->collection(
            $accountingProductTotals->getAccountingProducts(),
            new AccountingProductTransformer(),
            'accountingProduct'
        );
    }
}
