<?php

namespace Railroad\Ecommerce\Entities\Structures;

use DateTimeInterface;

class SubscriptionStateInterval
{
    const TYPE_ACTIVE = 'active';
    const TYPE_SUSPENDED = 'suspended';
    const TYPE_CANCELED = 'canceled';

    /**
     * @var \DateTime
     */
    private $start;

    /**
     * @var \DateTime
     */
    private $end;

    /**
     * @var string
     */
    private $type;

    public function __construct(
        ?DateTimeInterface $start = null,
        ?DateTimeInterface $end = null,
        ?string $type = null
    )
    {
        $this->start = $start;
        $this->end = $end;
        $this->type = $type;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getStart(): ?DateTimeInterface
    {
        return $this->start;
    }

    /**
     * @param DateTimeInterface $start
     */
    public function setStart(DateTimeInterface $start)
    {
        $this->start = $start;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getEnd(): ?DateTimeInterface
    {
        return $this->end;
    }

    /**
     * @param DateTimeInterface $end
     */
    public function setEnd(DateTimeInterface $end)
    {
        $this->end = $end;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }
}
