<?php

namespace Railroad\Ecommerce\Entities;

use Carbon\Carbon;
use Railroad\Doctrine\Contracts\UserEntityInterface;
use Railroad\Ecommerce\Contracts\IdentifiableInterface;

class User implements UserEntityInterface, IdentifiableInterface
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $email;

    private ?Carbon $membershipExpirationDate;

    /**
     * User constructor.
     *
     * @param int $id
     * @param string $email
     */
    public function __construct(int $id, string $email, ?Carbon $membershipExpirationDate = null)
    {
        $this->id = $id;
        $this->email = $email;
        $this->membershipExpirationDate = $membershipExpirationDate;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }


    public function getMembershipExpirationDate(): ?Carbon
    {
        return $this->membershipExpirationDate;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        /*
        method needed by UnitOfWork
        https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/cookbook/custom-mapping-types.html
        */
        return (string)$this->getId();
    }
}
