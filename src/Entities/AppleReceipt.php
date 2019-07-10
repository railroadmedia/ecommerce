<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\AppleReceiptRepository")
 * @ORM\Table(
 *     name="ecommerce_apple_receipts",
 *     indexes={
 *     }
 * )
 */
class AppleReceipt
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
    protected $receipt;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $email;

    /**
     * Field is not persisted
     *
     * @var string
     */
    protected $password;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $brand;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    protected $valid;

    /**
     * @ORM\Column(type="string", name="validation_error", nullable=true)
     *
     * @var string
     */
    protected $validationError;

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
    public function getReceipt(): ?string
    {
        return $this->receipt;
    }

    /**
     * @param string $receipt
     */
    public function setReceipt(string $receipt)
    {
        $this->receipt = $receipt;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email)
    {
        $this->email = $email;
    }

    /**
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password)
    {
        $this->password = $password;
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
     * @return bool|null
     */
    public function getValid(): ?bool
    {
        return $this->valid;
    }

    /**
     * @param bool $valid
     */
    public function setValid(bool $valid)
    {
        $this->valid = $valid;
    }

    /**
     * @return string|null
     */
    public function getValidationError(): ?string
    {
        return $this->validationError;
    }

    /**
     * @param string $validationError
     */
    public function setValidationError(string $validationError)
    {
        $this->validationError = $validationError;
    }
}
