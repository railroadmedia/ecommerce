<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\Structures\AccountingProduct;

class AccountingProductTransformer extends TransformerAbstract
{
    /**
     * @param AccountingProduct $accountingProduct
     *
     * @return array
     */
    public function transform(AccountingProduct $accountingProduct)
    {
        return [
            'id' => $accountingProduct->getId(),
            'name' => $accountingProduct->getName(),
            'sku' => $accountingProduct->getSku(),
            'tax_paid' => $accountingProduct->getTaxPaid(),
            'shipping_paid' => $accountingProduct->getShippingPaid(),
            'finance_paid' => $accountingProduct->getFinancePaid(),
            'less_refunded' => $accountingProduct->getLessRefunded(),
            'total_quantity' => $accountingProduct->getTotalQuantity(),
            'refunded_quantity' => $accountingProduct->getRefundedQuantity(),
            'free_quantity' => $accountingProduct->getFreeQuantity(),
            'net_product' => $accountingProduct->getNetProduct(),
            'net_paid' => $accountingProduct->getNetPaid(),
        ];
    }
}
