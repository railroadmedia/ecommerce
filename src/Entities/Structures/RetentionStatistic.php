<?php

namespace Railroad\Ecommerce\Entities\Structures;

class RetentionStatistic
{
    /**
     * @var string
     */
    private $brand;

    /**
     * @var string
     */
    private $subscriptionType;

    /**
     * @var integer
     */
    private $totalUsersInPool;

    /**
     * @var integer
     */
    private $totalUsersWhoUpgradedOrRepurchased;

    /**
     * @var integer
     */
    private $totalUsersWhoRenewed;

    /**
     * @var integer
     */
    private $totalUsersWhoCanceledOrExpired;

    /**
     * @var float
     */
    private $retentionRate;

    /**
     * @var string
     */
    private $intervalStartDateTime;

    /**
     * @var string
     */
    private $intervalEndDateTime;

    /**
     * @return string
     */
    public function getBrand(): string
    {
        return $this->brand;
    }

    /**
     * @param string $brand
     */
    public function setBrand(string $brand): void
    {
        $this->brand = $brand;
    }

    /**
     * @return string
     */
    public function getSubscriptionType(): string
    {
        return $this->subscriptionType;
    }

    /**
     * @param string $subscriptionType
     */
    public function setSubscriptionType(string $subscriptionType): void
    {
        $this->subscriptionType = $subscriptionType;
    }

    /**
     * @return int
     */
    public function getTotalUsersInPool(): int
    {
        return $this->totalUsersInPool;
    }

    /**
     * @param int $totalUsersInPool
     */
    public function setTotalUsersInPool(int $totalUsersInPool): void
    {
        $this->totalUsersInPool = $totalUsersInPool;
    }

    /**
     * @return int
     */
    public function getTotalUsersWhoUpgradedOrRepurchased(): int
    {
        return $this->totalUsersWhoUpgradedOrRepurchased;
    }

    /**
     * @param int $totalUsersWhoUpgradedOrRepurchased
     */
    public function setTotalUsersWhoUpgradedOrRepurchased(int $totalUsersWhoUpgradedOrRepurchased): void
    {
        $this->totalUsersWhoUpgradedOrRepurchased = $totalUsersWhoUpgradedOrRepurchased;
    }

    /**
     * @return int
     */
    public function getTotalUsersWhoRenewed(): int
    {
        return $this->totalUsersWhoRenewed;
    }

    /**
     * @param int $totalUsersWhoRenewed
     */
    public function setTotalUsersWhoRenewed(int $totalUsersWhoRenewed): void
    {
        $this->totalUsersWhoRenewed = $totalUsersWhoRenewed;
    }

    /**
     * @return int
     */
    public function getTotalUsersWhoCanceledOrExpired(): int
    {
        return $this->totalUsersWhoCanceledOrExpired;
    }

    /**
     * @param int $totalUsersWhoCanceledOrExpired
     */
    public function setTotalUsersWhoCanceledOrExpired(int $totalUsersWhoCanceledOrExpired): void
    {
        $this->totalUsersWhoCanceledOrExpired = $totalUsersWhoCanceledOrExpired;
    }

    /**
     * @return float
     */
    public function getRetentionRate(): float
    {
        return $this->retentionRate;
    }

    /**
     * @param float $retentionRate
     */
    public function setRetentionRate(float $retentionRate): void
    {
        $this->retentionRate = $retentionRate;
    }

    /**
     * @return string
     */
    public function getIntervalStartDateTime(): string
    {
        return $this->intervalStartDateTime;
    }

    /**
     * @param string $intervalStartDateTime
     */
    public function setIntervalStartDateTime(string $intervalStartDateTime): void
    {
        $this->intervalStartDateTime = $intervalStartDateTime;
    }

    /**
     * @return string
     */
    public function getIntervalEndDateTime(): string
    {
        return $this->intervalEndDateTime;
    }

    /**
     * @param string $intervalEndDateTime
     */
    public function setIntervalEndDateTime(string $intervalEndDateTime): void
    {
        $this->intervalEndDateTime = $intervalEndDateTime;
    }
}
