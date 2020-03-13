<?php

namespace Railroad\Ecommerce\Entities\Structures;

use Carbon\Carbon;

class RetentionStatistic
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $brand;

    /**
     * @var string
     */
    private $subscriptionType;

    /**
     * @var float
     */
    private $retentionRate;

    /**
     * @var Carbon
     */
    private $intervalStartDate;

    /**
     * @var Carbon
     */
    private $intervalEndDate;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getBrand(): ?string
    {
        return $this->brand;
    }

    /**
     * @param string $brand
     */
    public function setBrand(string $brand)
    {
        $this->brand = $brand;
    }

    /**
     * @return string|null
     */
    public function getSubscriptionType(): ?string
    {
        return $this->subscriptionType;
    }

    /**
     * @param string $subscriptionType
     */
    public function setSubscriptionType(string $subscriptionType)
    {
        $this->subscriptionType = $subscriptionType;
    }

    /**
     * @return float|null
     */
    public function getRetentionRate(): ?float
    {
        return $this->retentionRate;
    }

    /**
     * @param float $retentionRate
     */
    public function setRetentionRate(float $retentionRate)
    {
        $this->retentionRate = $retentionRate;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getIntervalStartDate(): ?\DateTimeInterface
    {
        return $this->intervalStartDate;
    }

    /**
     * @param \DateTimeInterface $intervalStartDate
     */
    public function setIntervalStartDate(?\DateTimeInterface $intervalStartDate)
    {
        $this->intervalStartDate = $intervalStartDate;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getIntervalEndDate(): ?\DateTimeInterface
    {
        return $this->intervalEndDate;
    }

    /**
     * @param \DateTimeInterface $intervalEndDate
     */
    public function setIntervalEndDate(?\DateTimeInterface $intervalEndDate)
    {
        $this->intervalEndDate = $intervalEndDate;
    }
}
