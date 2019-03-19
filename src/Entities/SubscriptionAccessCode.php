<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="ecommerce_subscription_access_codes",
 *     indexes={
 *         @ORM\Index(name="ecommerce_subscription_access_codes_subscription_id_index", columns={"subscription_id"}),
 *         @ORM\Index(name="ecommerce_subscription_access_codes_access_code_id_index", columns={"access_code_id"}),
 *         @ORM\Index(name="ecommerce_subscription_access_codes_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_subscription_access_codes_updated_on_index", columns={"updated_at"})
 *     }
 * )
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

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Subscription|null
     */
    public function getSubscription(): ?Subscription
    {
        return $this->subscription;
    }

    /**
     * @param Subscription $subscription
     *
     * @return SubscriptionAccessCode
     */
    public function setSubscription(?Subscription $subscription): self
    {
        $this->subscription = $subscription;

        return $this;
    }

    /**
     * @return AccessCode|null
     */
    public function getAccessCode(): ?AccessCode
    {
        return $this->accessCode;
    }

    /**
     * @param AccessCode $accessCode
     *
     * @return SubscriptionAccessCode
     */
    public function setAccessCode(?AccessCode $accessCode): self
    {
        $this->accessCode = $accessCode;

        return $this;
    }
}
