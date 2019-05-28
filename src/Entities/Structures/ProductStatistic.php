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

    public function __construct(string $id, string $sku)
    {
        $this->id = $id;
        $this->sku = $sku;
        $this->totalQuantitySold = 0;
        $this->totalSales = 0;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string $id
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
     * @return string $sku
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
}
