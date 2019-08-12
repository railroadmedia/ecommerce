<?php

namespace Railroad\Ecommerce\Entities\Structures;

class DailyStatistic
{
    /**
     * @var string
     */
    private $day;

    /**
     * @var float
     */
    private $totalSales;

    /**
     * @var float
     */
    private $totalSalesFromRenewals;

    /**
     * @var float
     */
    private $totalRefunded;

    /**
     * @var int
     */
    private $totalOrders;

    /**
     * @var int
     */
    private $totalSuccessfulRenewals;

    /**
     * @var int
     */
    private $totalFailedRenewals;

    /**
     * @var ProductStatistic[]
     */
    private $productStatistics;

    public function __construct(string $day)
    {
        $this->day = $day;
        $this->totalSales = 0;
        $this->totalSalesFromRenewals = 0;
        $this->totalRefunded = 0;
        $this->totalOrders = 0;
        $this->totalSuccessfulRenewals = 0;
        $this->totalFailedRenewals = 0;
        $this->productStatistics = [];
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->day;
    }

    /**
     * @return string|null
     */
    public function getDay(): ?string
    {
        return $this->day;
    }

    /**
     * @param string $day
     */
    public function setDay(?string $day)
    {
        $this->day = $day;
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
    public function getTotalSalesFromRenewals(): ?float
    {
        return $this->totalSalesFromRenewals;
    }

    /**
     * @param float $totalSalesFromRenewals
     */
    public function setTotalSalesFromRenewals(float $totalSalesFromRenewals)
    {
        $this->totalSalesFromRenewals = $totalSalesFromRenewals;
    }

    /**
     * @return float|null
     */
    public function getTotalRefunded(): ?float
    {
        return $this->totalRefunded;
    }

    /**
     * @param float $totalRefunded
     */
    public function setTotalRefunded(float $totalRefunded)
    {
        $this->totalRefunded = $totalRefunded;
    }

    /**
     * @return int|null
     */
    public function getTotalOrders(): ?int
    {
        return $this->totalOrders;
    }

    /**
     * @param int $totalOrders
     */
    public function setTotalOrders(int $totalOrders)
    {
        $this->totalOrders = $totalOrders;
    }

    /**
     * @return int|null
     */
    public function getTotalSuccessfulRenewals(): ?int
    {
        return $this->totalSuccessfulRenewals;
    }

    /**
     * @param int $totalSuccessfulRenewals
     */
    public function setTotalSuccessfulRenewals(int $totalSuccessfulRenewals)
    {
        $this->totalSuccessfulRenewals = $totalSuccessfulRenewals;
    }

    /**
     * @return int|null
     */
    public function getTotalFailedRenewals(): ?int
    {
        return $this->totalFailedRenewals;
    }

    /**
     * @param int $totalFailedRenewals
     */
    public function setTotalFailedRenewals(int $totalFailedRenewals)
    {
        $this->totalFailedRenewals = $totalFailedRenewals;
    }

    /**
     * Get product statistics
     *
     * @return ProductStatistic[]
     */
    public function getProductStatistics(): array
    {
        return $this->productStatistics;
    }

    /**
     * @param ProductStatistic $productStatistic
     */
    public function addProductStatistics(ProductStatistic $productStatistic)
    {
        $this->productStatistics[] = $productStatistic;
    }
}
