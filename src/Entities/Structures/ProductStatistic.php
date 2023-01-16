<?php

namespace Railroad\Ecommerce\Entities\Structures;

class ProductStatistic
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $sku;

    /**
     * @var int
     */
    private $totalQuantitySold;

    /**
     * @var float
     */
    private $totalSales;

    /**
     * @var float
     */
    private $totalRenewalSales;

    /**
     * @var float
     */
    private $totalRenewals;

    /**
     * @var float
     */
    private $totalExpectedRenewalValue;

    public function __construct(string $id, string $sku)
    {
        $this->id = $id;
        $this->sku = $sku;
        $this->totalQuantitySold = 0;
        $this->totalSales = 0;
        $this->totalRenewalSales = 0;
        $this->totalRenewals = 0;
        $this->totalExpectedRenewalValue = 0;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getSku()
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
     * @return int|null
     */
    public function getTotalQuantitySold(): ?int
    {
        return $this->totalQuantitySold;
    }

    /**
     * @param int $totalQuantitySold
     */
    public function setTotalQuantitySold(int $totalQuantitySold)
    {
        $this->totalQuantitySold = $totalQuantitySold;
    }

    /**
     * @return float|null
     */
    public function getTotalSales(): ?float
    {
        return $this->totalSales;
    }

    /**
     * @param float $totalSales
     */
    public function setTotalSales(float $totalSales)
    {
        $this->totalSales = $totalSales;
    }

    /**
     * @return float|null
     */
    public function getTotalRenewalSales(): ?float
    {
        return $this->totalRenewalSales;
    }

    /**
     * @param float $totalRenewalSales
     */
    public function setTotalRenewalSales(float $totalRenewalSales)
    {
        $this->totalRenewalSales = $totalRenewalSales;
    }

    /**
     * @return float
     */
    public function getTotalRenewals(): float
    {
        return $this->totalRenewals;
    }

    /**
     * @param float $totalRenewals
     */
    public function setTotalRenewals(float $totalRenewals): void
    {
        $this->totalRenewals = $totalRenewals;
    }

    /**
     * @return float|int
     */
    public function getTotalExpectedRenewalValue()
    {
        return $this->totalExpectedRenewalValue;
    }

    /**
     * @param float|int $totalExpectedRenewalValue
     */
    public function setTotalExpectedRenewalValue($totalExpectedRenewalValue)
    {
        $this->totalExpectedRenewalValue = $totalExpectedRenewalValue;
    }
}
