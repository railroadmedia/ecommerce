<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\AppleReceiptRepository")
 * @ORM\Table(
 *     name="ecommerce_addresses",
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
    protected $receiptKey;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    private $email;

    /**
     * Field is not persisted
     *
     * @var string
     */
    private $password;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    private $valid;

    /**
     * @ORM\Column(type="string", name="validation_error", nullable=true)
     *
     * @var string
     */
    private $validationError;
}
