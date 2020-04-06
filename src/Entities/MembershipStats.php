<?php

namespace Railroad\Ecommerce\Entities;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\MembershipStatsRepository")
 * @ORM\Table(
 *     name="ecommerce_membership_stats",
 *     indexes={
 *     }
 * )
 */
class MembershipStats
{
    use TimestampableEntity;

    const TYPE_ONE_MONTH = 'one_month';
    const TYPE_SIX_MONTHS = 'six_months';
    const TYPE_ONE_YEAR = 'one_year';
    const TYPE_LIFETIME = 'lifetime';
    const TYPE_ALL = 'all';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    protected $new;

    /**
     * @ORM\Column(type="integer", name="active_state")
     *
     * @var int
     */
    protected $activeState;

    /**
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    protected $expired;

    /**
     * @ORM\Column(type="integer", name="suspended_state")
     *
     * @var int
     */
    protected $suspendedState;

    /**
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    protected $canceled;

    /**
     * @ORM\Column(type="integer", name="canceled_state")
     *
     * @var int
     */
    protected $canceledState;

    /**
     * @ORM\Column(type="string", name="interval_type")
     *
     * @var string
     */
    protected $intervalType;

    /**
     * @ORM\Column(type="date", name="stats_date")
     *
     * @var DateTime
     */
    protected $statsDate;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $brand;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function getNew(): ?int
    {
        return $this->new;
    }

    /**
     * @param int $new
     */
    public function setNew(int $new)
    {
        $this->new = $new;
    }

    /**
     * @return int|null
     */
    public function getActiveState(): ?int
    {
        return $this->activeState;
    }

    /**
     * @param int $activeState
     */
    public function setActiveState(int $activeState)
    {
        $this->activeState = $activeState;
    }

    /**
     * @return int|null
     */
    public function getExpired(): ?int
    {
        return $this->expired;
    }

    /**
     * @param int $expired
     */
    public function setExpired(int $expired)
    {
        $this->expired = $expired;
    }

    /**
     * @return int|null
     */
    public function getSuspendedState(): ?int
    {
        return $this->suspendedState;
    }

    /**
     * @param int $suspendedState
     */
    public function setSuspendedState(int $suspendedState)
    {
        $this->suspendedState = $suspendedState;
    }

    /**
     * @return int|null
     */
    public function getCanceled(): ?int
    {
        return $this->canceled;
    }

    /**
     * @param int $canceled
     */
    public function setCanceled(int $canceled)
    {
        $this->canceled = $canceled;
    }

    /**
     * @return int|null
     */
    public function getCanceledState(): ?int
    {
        return $this->canceledState;
    }

    /**
     * @param int $canceledState
     */
    public function setCanceledState(int $canceledState)
    {
        $this->canceledState = $canceledState;
    }

    /**
     * @return string|null
     */
    public function getIntervalType(): ?string
    {
        return $this->intervalType;
    }

    /**
     * @param string $intervalType
     */
    public function setIntervalType(string $intervalType)
    {
        $this->intervalType = $intervalType;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getStatsDate(): ?DateTimeInterface
    {
        return $this->statsDate;
    }

    /**
     * @param DateTimeInterface $statsDate
     */
    public function setStatsDate(?DateTimeInterface $statsDate)
    {
        $this->statsDate = $statsDate;
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
}
