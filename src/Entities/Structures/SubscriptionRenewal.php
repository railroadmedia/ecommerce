<?php

namespace Railroad\Ecommerce\Entities\Structures;

use DateTimeInterface;

class SubscriptionRenewal
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var int
     */
    private $userId;

    /**
     * @var string
     */
    private $brand;

    /**
     * @var int
     */
    private $subscriptionId;

    /**
     * @var string
     */
    private $subscriptionType;

    /**
     * @var string
     */
    private $subscriptionState;

    /**
     * @var DateTimeInterface|null
     */
    private $nextRenewalDue;

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
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId(int $userId)
    {
        $this->userId = $userId;
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
     * @return int|null
     */
    public function getSubscriptionId(): ?int
    {
        return $this->subscriptionId;
    }

    /**
     * @param int $subscriptionId
     */
    public function setSubscriptionId(int $subscriptionId)
    {
        $this->subscriptionId = $subscriptionId;
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
     * @return string|null
     */
    public function getSubscriptionState(): ?string
    {
        return $this->subscriptionState;
    }

    /**
     * @param string $subscriptionState
     */
    public function setSubscriptionState(string $subscriptionState)
    {
        $this->subscriptionState = $subscriptionState;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getNextRenewalDue(): ?DateTimeInterface
    {
        return $this->nextRenewalDue;
    }

    /**
     * @param DateTimeInterface|null $nextRenewalDue
     */
    public function setNextRenewalDue(?DateTimeInterface $nextRenewalDue)
    {
        $this->nextRenewalDue = $nextRenewalDue;
    }
}
