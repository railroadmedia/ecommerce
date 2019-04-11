<?php

namespace Railroad\Ecommerce\Entities\Structures;

/**
 * This class represent either a user or a customer. We use it as an interface instead of having to always
 * use if statements and figure out if an action is for a existing user or a customer (guest).
 *
 * @package Railroad\Ecommerce\Entities\Structures
 */
class Purchaser
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string|null
     */
    private $rawPassword;

    /**
     * @var string
     */
    private $brand;

    /**
     * Default to customer.
     *
     * @var string
     */
    private $type = self::CUSTOMER_TYPE;

    const USER_TYPE = 'user';
    const CUSTOMER_TYPE = 'customer';

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
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
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string|null
     */
    public function getRawPassword(): ?string
    {
        return $this->rawPassword;
    }

    /**
     * @param string|null $rawPassword
     */
    public function setRawPassword(?string $rawPassword): void
    {
        $this->rawPassword = $rawPassword;
    }

    /**
     * @return string
     */
    public function getBrand(): string
    {
        return $this->brand;
    }

    /**
     * @param string $brand
     */
    public function setBrand(string $brand): void
    {
        $this->brand = $brand;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }
}