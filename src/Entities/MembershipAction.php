<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Railroad\Ecommerce\Entities\Traits\NotableEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\MembershipActionRepository")
 * @ORM\Table(
 *     name="ecommerce_membership_actions",
 *     indexes={
 *         @ORM\Index(name="ecommerce_membership_actions_user_id_index", columns={"user_id"}),
 *         @ORM\Index(name="ecommerce_membership_actions_action_index", columns={"action"}),
 *         @ORM\Index(name="ecommerce_membership_actions_action_amount_index", columns={"action_amount"}),
 *         @ORM\Index(name="ecommerce_membership_actions_subscription_id_index", columns={"subscription_id"}),
 *         @ORM\Index(name="ecommerce_membership_actions_brand_index", columns={"brand"}),
 *     }
 * )
 * @ORM\HasLifecycleCallbacks
 */
class MembershipAction
{
    use TimestampableEntity, NotableEntity;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $action;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @var int|null
     */
    protected $actionAmount = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string|null
     */
    protected $actionReason = null;

    /**
     * @var User|null
     *
     * @ORM\Column(type="user", name="user_id", nullable=true)
     */
    protected $user;

    /**
     * @var Subscription|null
     *
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\Subscription")
     * @ORM\JoinColumn(name="subscription_id", referencedColumnName="id", nullable=true)
     */
    protected $subscription;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $brand;

    public const ACTION_PAUSE_FOR_AMOUNT_OF_DAYS = 'paused for amount of days';
    public const ACTION_EXTEND_FOR_AMOUNT_OF_DAYS = 'extended for amount of days';
    public const ACTION_EXTEND_FOR_AMOUNT_OF_DAYS_GRATIS_FOR_RETENTION =
        'extended for amount of days gratis for retention offer';
    public const ACTION_EXTEND_FOR_AMOUNT_OF_MONTHS = 'extended for amount of months';
    public const ACTION_EXTEND_FOR_AMOUNT_OF_MONTH_GRATIS_FOR_RETENTION =
        'extended for amount of months gratis for retention offer';
    public const ACTION_SWITCH_TO_NEW_PRICE = 'switched to new price';
    public const ACTION_SWITCH_TO_NEW_PRICE_IN_CENTS = 'switched to new price in cents';
    public const ACTION_SWITCH_BILLING_INTERVAL_TO_MONTHLY = 'switched billing interval to monthly';
    public const ACTION_SWITCH_BILLING_INTERVAL_TO_YEARLY = 'switched billing interval to yearly';
    public const ACTION_REQUESTED_HELP = 'requested help';
    public const ACTION_REQUESTED_TECHNICAL_SUPPORT = 'requested technical support';
    public const ACTION_CANCELLED = 'cancelled';
    public const ACTION_CANCELLATION_WINBACK = 'accepted cancellation winback offer';
    public const ACTION_CANCELLATION_RETENTION = 'accepted retention offer';

    /**
     * Address constructor.
     */
    public function __construct()
    {
        $this->brand = config('ecommerce.brand');
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * @return int|null
     */
    public function getActionAmount()
    {
        return $this->actionAmount;
    }

    /**
     * @param int|null $actionAmount
     */
    public function setActionAmount($actionAmount = null)
    {
        $this->actionAmount = $actionAmount;
    }

    /**
     * @return string|null
     */
    public function getActionReason()
    {
        return $this->actionReason;
    }

    /**
     * @param string|null $actionReason
     */
    public function setActionReason($actionReason): void
    {
        $this->actionReason = $actionReason;
    }

    /**
     * @return User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;
    }

    /**
     * @return Subscription|null
     */
    public function getSubscription()
    {
        return $this->subscription;
    }

    /**
     * @param Subscription|null $subscription
     */
    public function setSubscription(Subscription $subscription = null)
    {
        $this->subscription = $subscription;
    }

    /**
     * @return string
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * @param string $brand
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;
    }
}
