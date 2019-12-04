<?php

namespace Railroad\Ecommerce\Events;

class AppSignupFinishedEvent
{
    /**
     * @var array
     */
    public $attributes;

    /**
     * AppSignupFinishedEvent constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * @return array|null
     */
    public function getAttributes(): ?array
    {
        return $this->attributes;
    }
}
