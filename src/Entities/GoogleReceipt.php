<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\GoogleRepository")
 * @ORM\Table(
 *     name="ecommerce_google_receipts",
 *     indexes={
 *     }
 * )
 */
class GoogleReceipt
{
    use TimestampableEntity;

    CONST MOBILE_APP_REQUEST_TYPE = 'mobile';
    CONST APPLE_NOTIFICATION_REQUEST_TYPE = 'notification';

    CONST APPLE_RENEWAL_NOTIFICATION_TYPE = 'renewal';
    CONST APPLE_CANCEL_NOTIFICATION_TYPE = 'cancel';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    protected $id;

    /**
     *
     *
     * @ORM\Column(type="string", name="purchase_token")
     *
     * @var string
     */
    protected $purchaseToken;

    /**
     *
     *
     * @ORM\Column(type="string", name="package_name")
     *
     * @var string
     */
    protected $packageName;

    /**
     *
     *
     * @ORM\Column(type="string", name="product_id")
     *
     * @var string
     */
    protected $productId;

    /**
     * Mobile app or apple notification
     *
     * @ORM\Column(type="string", name="request_type")
     *
     * @var string
     */
    protected $requestType;

    /**
     * For apple notification only, renewal or cancel
     *
     * @ORM\Column(type="string", name="notification_type", nullable=true)
     *
     * @var string
     */
    protected $notificationType;

    /**
     * @ORM\Column(type="string", nullable=true)
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
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\Payment")
     * @ORM\JoinColumn(name="payment_id", referencedColumnName="id")
     */
    protected $payment;

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
    public function getPurchaseToken(): ?string
    {
        return $this->purchaseToken;
    }

    /**
     * @param string $purchaseToken
     */
    public function setPurchaseToken(string $purchaseToken)
    {
        $this->purchaseToken = $purchaseToken;
    }

    /**
     * @return string|null
     */
    public function getPackageName(): ?string
    {
        return $this->packageName;
    }

    /**
     * @param string $packageName
     */
    public function setPackageName(string $packageName)
    {
        $this->packageName = $packageName;
    }

    /**
     * @return string|null
     */
    public function getProductId(): ?string
    {
        return $this->productId;
    }

    /**
     * @param string $productId
     */
    public function setProductId(string $productId)
    {
        $this->productId = $productId;
    }

    /**
     * @return string|null
     */
    public function getRequestType(): ?string
    {
        return $this->requestType;
    }

    /**
     * @param string $requestType
     */
    public function setRequestType(string $requestType)
    {
        $this->requestType = $requestType;
    }

    /**
     * @return string|null
     */
    public function getNotificationType(): ?string
    {
        return $this->notificationType;
    }

    /**
     * @param string $notificationType
     */
    public function setNotificationType(string $notificationType)
    {
        $this->notificationType = $notificationType;
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

    /**
     * @return Payment|null
     */
    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    /**
     * @param Payment $payment
     */
    public function setPayment(?Payment $payment)
    {
        $this->payment = $payment;
    }
}
