<?php

namespace Railroad\Ecommerce\Exceptions;

use Exception;
use Railroad\Ecommerce\Entities\Payment;
use Throwable;

class PaymentFailedException extends Exception
{
    /**
     * @var Payment
     */
    protected $payment;

    /**
     * @param string $message
     * @param int $code
     * @param Throwable $previous
     * @param Payment $payment
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        Throwable $previous = null,
        Payment $payment = null
    )
    {
        parent::__construct($message, $code, $previous);

        $this->payment = $payment;
    }

    /**
     * @param Throwable|null $exception
     * @param Payment $payment
     *
     * @return string|null
     */
    public static function createFromException(
        ?Throwable $exception,
        Payment $payment
    ): PaymentFailedException
    {
        return new static(
            $exception->getMessage(),
            $exception->getCode(),
            $exception,
            $payment
        );
    }

    /**
     * @return Payment|null
     */
    public function getPayment(): ?Payment
    {
        return $this->payment;
    }
}
