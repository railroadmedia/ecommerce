<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\RetentionStatsRepository")
 * @ORM\Table(
 *     name="ecommerce_retention_stats",
 *     indexes={
 *     }
 * )
 */
class RetentionStats
{
    use TimestampableEntity;

    const TYPE_ONE_MONTH = 'one_month';
    const TYPE_SIX_MONTHS = 'six_months';
    const TYPE_ONE_YEAR = 'one_year';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="string", name="subscription_type")
     *
     * @var string
     */
    protected $subscriptionType;

    /**
     * @ORM\Column(type="date", name="interval_start_date")
     *
     * @var \DateTime
     */
    protected $intervalStartDate;

    /**
     * @ORM\Column(type="date", name="interval_end_date")
     *
     * @var \DateTime
     */
    protected $intervalEndDate;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $brand;

    /**
     * @ORM\Column(type="integer", name="customers_start")
     *
     * @var int
     */
    protected $customersStart;

    /**
     * @ORM\Column(type="integer", name="customers_end")
     *
     * @var int
     */
    protected $customersEnd;

    /**
     * @ORM\Column(type="integer", name="customers_new")
     *
     * @var int
     */
    protected $customersNew;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
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
    public function getCustomersStart(): ?int
    {
        return $this->customersStart;
    }

    /**
     * @param int $customersStart
     */
    public function setCustomersStart(?int $customersStart)
    {
        $this->customersStart = $customersStart;
    }

    /**
     * @return int|null
     */
    public function getCustomersEnd(): ?int
    {
        return $this->customersEnd;
    }

    /**
     * @param int $customersEnd
     */
    public function setCustomersEnd(?int $customersEnd)
    {
        $this->customersEnd = $customersEnd;
    }

    /**
     * @return int|null
     */
    public function getCustomersNew(): ?int
    {
        return $this->customersNew;
    }

    /**
     * @param int $customersNew
     */
    public function setCustomersNew(?int $customersNew)
    {
        $this->customersNew = $customersNew;
    }
}
