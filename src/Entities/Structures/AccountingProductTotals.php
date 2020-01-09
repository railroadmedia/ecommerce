<?php

namespace Railroad\Ecommerce\Entities\Structures;

class AccountingProductTotals
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var float
     */
    private $taxPaid;

    /**
     * @var float
     */
    private $shippingPaid;

    /**
     * @var float
     */
    private $financePaid;

    /**
     * @var float
     */
    private $refunded;

    /**
     * @var float
     */
    private $netProduct;

    /**
     * @var float
     */
    private $netPaid;

    /**
     * @var AccountingProduct[]
     */
    private $accountingProducts;

    public function __construct(string $smallDate, string $bigDate)
    {
        $format = '%s - %s';
        $this->id = sprintf($format, $smallDate, $bigDate);
        $this->accountingProducts = [];
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return float|null
     */
    public function getTaxPaid(): ?float
    {
        return $this->taxPaid;
    }

    /**
     * @param float $taxPaid
     */
    public function setTaxPaid(float $taxPaid)
    {
        $this->taxPaid = $taxPaid;
    }

    /**
     * @return float|null
     */
    public function getShippingPaid(): ?float
    {
        return $this->shippingPaid;
    }

    /**
     * @param float $shippingPaid
     */
    public function setShippingPaid(float $shippingPaid)
    {
        $this->shippingPaid = $shippingPaid;
    }

    /**
     * @return float|null
     */
    public function getFinancePaid(): ?float
    {
        return $this->financePaid;
    }

    /**
     * @param float $financePaid
     */
    public function setFinancePaid(float $financePaid)
    {
        $this->financePaid = $financePaid;
    }

    /**
     * @return float|null
     */
    public function getRefunded(): ?float
    {
        return $this->refunded;
    }

    /**
     * @param float $refunded
     */
    public function setRefunded(float $refunded)
    {
        $this->refunded = $refunded;
    }

    /**
     * @return float|null
     */
    public function getNetProduct(): ?float
    {
        return $this->netProduct;
    }

    /**
     * @param float $netProduct
     */
    public function setNetProduct(float $netProduct)
    {
        $this->netProduct = $netProduct;
    }

    /**
     * @return float|null
     */
    public function getNetPaid(): ?float
    {
        return $this->netPaid;
    }

    /**
     * @param float $netPaid
     */
    public function setNetPaid(float $netPaid)
    {
        $this->netPaid = $netPaid;
    }

    /**
     * Get product statistics
     *
     * @return AccountingProduct[]
     */
    public function getAccountingProducts(): array
    {
        return $this->accountingProducts;
    }

    /**
     * @param AccountingProduct $accountingProduct
     */
    public function addProductStatistics(AccountingProduct $accountingProduct)
    {
        $this->accountingProducts[$accountingProduct->getId()] = $accountingProduct;
    }

    public function orderAccountingProductsBySku()
    {
        usort(
            $this->accountingProducts,
            function (AccountingProduct $a, AccountingProduct $b) {
                return strcmp($a->getSku(), $b->getSku());
            }
        );
    }
}
