<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(name="ecommerce_subscription_access_code")
 */
class SubscriptionAccessCode
{
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\Subscription")
     * @ORM\JoinColumn(name="subscription_id", referencedColumnName="id")
     */
    protected $subscription;

    /**
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\AccessCode")
     * @ORM\JoinColumn(name="access_code_id", referencedColumnName="id")
     */
    protected $accessCode;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubscription(): ?Subscription
    {
        return $this->subscription;
    }

    public function setSubscription(?Subscription $subscription): self
    {
        $this->subscription = $subscription;

        return $this;
    }

    public function getAccessCode(): ?AccessCode
    {
        return $this->accessCode;
    }

    public function setAccessCode(?AccessCode $accessCode): self
    {
        $this->accessCode = $accessCode;

        return $this;
    }
}
