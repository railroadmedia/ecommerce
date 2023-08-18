<?php

namespace Railroad\Ecommerce\Entities;

use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Railroad\Doctrine\Contracts\UserEntityInterface;
use Railroad\Ecommerce\Contracts\IdentifiableInterface;
use Railroad\Ecommerce\Entities\Traits\ShopifyEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\UserRepository")
 * @ORM\Table(
 *     name="usora_users",
 * )
 */
class User implements UserEntityInterface, IdentifiableInterface
{
    use TimestampableEntity, ShopifyEntity;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string
     */
    protected $email;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string
     */
    protected $first_name;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string
     */
    protected $last_name;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string
     */
    protected $support_note;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string
     */
    protected $phone_number;




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

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    /**
     * @param string|null $firstName
     */
    public function setFirstName(?string $firstName): void
    {
        $this->first_name = $firstName;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    /**
     * @param string|null $lastName
     */
    public function setLastName(?string $lastName): void
    {
        $this->last_name = $lastName;
    }

    /**
     * @return string|null
     */
    public function getSupportNote(): ?string
    {
        return $this->support_note;
    }

    /**
     * @param string|null $supportNote
     */
    public function setSupportNote(?string $supportNote): void
    {
        $this->support_note = $supportNote;
    }

    /**
     * @return string|null
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
    }

    /**
     * @param string|null $phoneNumber
     */
    public function setPhoneNumber(?string $phoneNumber): void
    {
        $this->phone_number = $phoneNumber;
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
