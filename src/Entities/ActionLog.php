<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\ActionLogRepository")
 * @ORM\Table(name="ecommerce_actions_log")
 */
class ActionLog
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
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $brand;

    /**
     * @ORM\Column(type="string", name="resource_name")
     *
     * @var string
     */
    protected $resourceName;

    /**
     * @ORM\Column(type="integer", name="resource_id")
     *
     * @var int
     */
    protected $resourceId;

    /**
     * @ORM\Column(type="string", name="action_name")
     *
     * @var string
     */
    protected $actionName;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $actor;

    /**
     * @ORM\Column(type="integer", name="actor_id", nullable=true)
     *
     * @var int
     */
    protected $actorId;

    /**
     * @ORM\Column(type="string", name="actor_role")
     *
     * @var string
     */
    protected $actorRole;

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
     * @return string|null
     */
    public function getResourceName(): ?string
    {
        return $this->resourceName;
    }

    /**
     * @param string $resourceName
     */
    public function setResourceName(string $resourceName)
    {
        $this->resourceName = $resourceName;
    }

    /**
     * @return int|null
     */
    public function getResourceId(): ?int
    {
        return $this->resourceId;
    }

    /**
     * @param int $resourceId
     */
    public function setResourceId(int $resourceId)
    {
        $this->resourceId = $resourceId;
    }

    /**
     * @return string|null
     */
    public function getActionName(): ?string
    {
        return $this->actionName;
    }

    /**
     * @param string $actionName
     */
    public function setActionName(string $actionName)
    {
        $this->actionName = $actionName;
    }

    /**
     * @return string|null
     */
    public function getActor(): ?string
    {
        return $this->actor;
    }

    /**
     * @param string $actor
     */
    public function setActor(string $actor)
    {
        $this->actor = $actor;
    }

    /**
     * @return int|null
     */
    public function getActorId(): ?int
    {
        return $this->actorId;
    }

    /**
     * @param int $actorId
     */
    public function setActorId(?int $actorId)
    {
        $this->actorId = $actorId;
    }

    /**
     * @return string|null
     */
    public function getActorRole(): ?string
    {
        return $this->actorRole;
    }

    /**
     * @param string $actorRole
     */
    public function setActorRole(string $actorRole)
    {
        $this->actorRole = $actorRole;
    }
}