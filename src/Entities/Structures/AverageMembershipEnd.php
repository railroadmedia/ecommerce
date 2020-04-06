<?php

namespace Railroad\Ecommerce\Entities\Structures;

use Carbon\Carbon;
use DateTimeInterface;

class AverageMembershipEnd
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
    private $averageMembershipEnd;

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
    public function getAverageMembershipEnd(): ?float
    {
        return $this->averageMembershipEnd;
    }

    /**
     * @param float $averageMembershipEnd
     */
    public function setAverageMembershipEnd(float $averageMembershipEnd)
    {
        $this->averageMembershipEnd = $averageMembershipEnd;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getIntervalStartDate(): ?DateTimeInterface
    {
        return $this->intervalStartDate;
    }

    /**
     * @param DateTimeInterface $intervalStartDate
     */
    public function setIntervalStartDate(?DateTimeInterface $intervalStartDate)
    {
        $this->intervalStartDate = $intervalStartDate;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getIntervalEndDate(): ?DateTimeInterface
    {
        return $this->intervalEndDate;
    }

    /**
     * @param DateTimeInterface $intervalEndDate
     */
    public function setIntervalEndDate(?DateTimeInterface $intervalEndDate)
    {
        $this->intervalEndDate = $intervalEndDate;
    }
}
