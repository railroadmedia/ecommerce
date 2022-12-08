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

    CONST MOBILE_APP_REQUEST_TYPE = 'mobile';
    CONST APPLE_NOTIFICATION_REQUEST_TYPE = 'notification';

    CONST APPLE_RENEWAL_NOTIFICATION_TYPE = 'DID_RENEW';
    CONST APPLE_CANCEL_NOTIFICATION_TYPE = 'CANCEL';

    CONST APPLE_PRODUCT_PURCHASE = 'product';
    CONST APPLE_SUBSCRIPTION_PURCHASE = 'subscription';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    protected $id;

    /**
     * Base64-encoded transaction receipt
     *
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
    protected $transactionId;

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
    protected $valid = false;

    /**
     * For apple notification only
     *
     * @ORM\Column(type="string", name="notification_request_data", nullable=true)
     *
     * @var string
     */
    protected $notificationRequestData;

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
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\Subscription", inversedBy="appleReceipt")
     * @ORM\JoinColumn(name="subscription_id", referencedColumnName="id")
     */
    protected $subscription;

    /**
     * @ORM\Column(type="text", name="raw_receipt_response", nullable=true)
     *
     * @var string
     */
    protected $rawReceiptResponse;

    /**
     * Product or Subscription purchase
     *
     * @ORM\Column(type="string", name="purchase_type")
     *
     * @var string
     */
    protected $purchaseType;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, name="local_price", nullable=true)
     *
     * @var float
     */
    protected $localPrice;

    /**
     * User's local currency
     *
     * @ORM\Column(type="string", name="local_currency", nullable=true)
     *
     * @var string
     */
    protected $localCurrency;


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
    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    /**
     * @param string $transactionId
     */
    public function setTransactionId(string $transactionId)
    {
        $this->transactionId = $transactionId;
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
    public function getNotificationRequestData(): ?string
    {
        return $this->notificationRequestData;
    }

    /**
     * @param string|null $notificationRequestData
     */
    public function setNotificationRequestData(?string $notificationRequestData)
    {
        $this->notificationRequestData = $notificationRequestData;
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
    public function setValidationError(?string $validationError)
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

    /**
     * @return Subscription|null
     */
    public function getSubscription(): ?Subscription
    {
        return $this->subscription;
    }

    /**
     * @param Subscription $subscription
     */
    public function setSubscription(?Subscription $subscription)
    {
        $this->subscription = $subscription;
    }

    /**
     * @return string|null
     */
    public function getRawReceiptResponse()
    {
        return $this->rawReceiptResponse;
    }

    /**
     * @param string|null $rawReceiptResponse
     */
    public function setRawReceiptResponse($rawReceiptResponse): void
    {
        $this->rawReceiptResponse = $rawReceiptResponse;
    }

    /**
     * @return string|null
     */
    public function getPurchaseType(): ?string
    {
        return $this->purchaseType;
    }

    /**
     * @param string $purchaseType
     */
    public function setPurchaseType(string $purchaseType)
    {
        $this->purchaseType = $purchaseType;
    }

    /**
     * @return float|null
     */
    public function getLocalPrice(): ?float
    {
        return $this->localPrice;
    }

    /**
     * @param float $localPrice
     */
    public function setLocalPrice(float $localPrice)
    {
        $this->localPrice = $localPrice;
    }

    /**
     * @return string|null
     */
    public function getLocalCurrency(): ?string
    {
        return $this->localCurrency;
    }

    /**
     * @param string $currency
     */
    public function setLocalCurrency(string $currency)
    {
        $this->localCurrency = $currency;
    }
}
