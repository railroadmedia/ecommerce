<?php

namespace Railroad\Ecommerce\Entities\Structures;

class AccountingProduct
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $sku;

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
    private $lessRefunded;

    /**
     * @var int
     */
    private $totalQuantity;

    /**
     * @var int
     */
    private $refundedQuantity;

    /**
     * @var int
     */
    private $freeQuantity;

    /**
     * @var float
     */
    private $netProduct;

    /**
     * @var float
     */
    private $netRecurringProduct = 0;

    /**
     * @var float
     */
    private $netPaid;

    public function __construct($productId)
    {
        $this->id = $productId;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getSku(): ?string
    {
        return $this->sku;
    }

    /**
     * @param string $sku
     */
    public function setSku(string $sku)
    {
        $this->sku = $sku;
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
    public function getLessRefunded(): ?float
    {
        return $this->lessRefunded;
    }

    /**
     * @param float $lessRefunded
     */
    public function setLessRefunded(float $lessRefunded)
    {
        $this->lessRefunded = $lessRefunded;
    }

    /**
     * @return int|null
     */
    public function getTotalQuantity(): ?int
    {
        return $this->totalQuantity;
    }

    /**
     * @param int $totalQuantity
     */
    public function setTotalQuantity(int $totalQuantity)
    {
        $this->totalQuantity = $totalQuantity;
    }

    /**
     * @return int|null
     */
    public function getRefundedQuantity(): ?int
    {
        return $this->refundedQuantity;
    }

    /**
     * @param int $refundedQuantity
     */
    public function setRefundedQuantity(int $refundedQuantity)
    {
        $this->refundedQuantity = $refundedQuantity;
    }

    /**
     * @return int|null
     */
    public function getFreeQuantity(): ?int
    {
        return $this->freeQuantity;
    }

    /**
     * @param int $freeQuantity
     */
    public function setFreeQuantity(int $freeQuantity)
    {
        $this->freeQuantity = $freeQuantity;
    }

    /**
     * @return float
     */
    public function getNetRecurringProduct(): float
    {
        return $this->netRecurringProduct;
    }

    /**
     * @param float $netRecurringProduct
     */
    public function setNetRecurringProduct(float $netRecurringProduct): void
    {
        $this->netRecurringProduct = $netRecurringProduct;
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
}
