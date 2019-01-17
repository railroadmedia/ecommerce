<?php

namespace Railroad\Ecommerce\Traits;

use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\HasLifecycleCallbacks
 */
trait TimestampableEntity
{
    /**
     * @ORM\Column(type="datetime", name="created_at")
     *
     * @var Carbon
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="datetime", name="updated_at")
     *
     * @var Carbon
     */
    protected $updatedAt;

    /**
     * @ORM\PrePersist
     */
    public function timestampableEntityPrePersist()
    {
        if (!$this->createdAt) {
            $this->createdAt = Carbon::now();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function timestampableEntityPreUpdate()
    {
        $this->updatedAt = Carbon::now();
    }

    /**
     * Sets createdAt.
     *
     * @param  Carbon $createdAt
     * @return $this
     */
    public function setCreatedAt(Carbon $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Returns createdAt.
     *
     * @return Carbon
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Sets updatedAt.
     *
     * @param  Carbon $updatedAt
     * @return $this
     */
    public function setUpdatedAt(Carbon $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Returns updatedAt.
     *
     * @return Carbon
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}
