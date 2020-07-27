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
     * @var User
     *
     * @ORM\Column(type="user", name="user_id", nullable=true)
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\Subscription")
     * @ORM\JoinColumn(name="subscription_id", referencedColumnName="id")
     */
    protected $subscription;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $brand;

    public const ACTION_PAUSE_FOR_AMOUNT_OF_DAYS = 'pause for amount of days';
    public const ACTION_EXTEND_FOR_AMOUNT_OF_DAYS = 'extend for amount of days';
    public const ACTION_SWITCH_TO_NEW_PRICE = 'switch to new price';
    public const ACTION_SWITCH_BILLING_INTERVAL_TO_MONTHLY = 'switch billing interval to monthly';
    public const ACTION_SWITCH_BILLING_INTERVAL_TO_YEARLY = 'switch billing interval to yearly';
    public const ACTION_REQUESTED_HELP = 'requested help';
    public const ACTION_REQUESTED_TECHNICAL_SUPPORT = 'requested technical support';
    public const ACTION_CANCELLED = 'cancelled';

    /**
     * Address constructor.
     */
    public function __construct()
    {
        $this->brand = config('ecommerce.brand');
    }
}
