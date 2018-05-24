<?php

namespace Railroad\Ecommerce\Gateways;

use Exception;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Exceptions\RefundFailedException;
use Railroad\Ecommerce\ExternalHelpers\PayPal;
use Railroad\Ecommerce\Services\ConfigService;

class PayPalPaymentGateway
{
    /**
     * @var PayPal
     */
    private $paypal;

    /**
     * PaypalPaymentGateway constructor.
     *
     * @param PayPal $paypal
     */
    public function __construct(PayPal $paypal)
    {
        $this->paypal = $paypal;
    }

    /**
     * @param string $gatewayName
     * @param float $amount
     * @param string $currency
     * @param $expressCheckoutToken
     * @param string $description
     * @return string
     * @throws PaymentFailedException
     */
    public function chargeToken($gatewayName, $amount, $currency, $expressCheckoutToken, $description = '')
    {
        $config = ConfigService::$paymentGateways['paypal'][$gatewayName];

        if (empty($config)) {
            throw new PaymentFailedException('Gateway ' . $gatewayName . ' is not configured.');
        }

        try {
            $this->paypal->configure($config);

            $billingAgreementId = $this->paypal->confirmAndCreateBillingAgreement($expressCheckoutToken);

            $transactionId = $this->paypal->createReferenceTransaction(
                $amount,
                $description,
                $billingAgreementId,
                $currency
            );
        } catch (Exception $exception) {
            throw new PaymentFailedException('Payment failed: ' . $exception->getMessage());
        }

        return $transactionId;
    }

    /**
     * @param string $gatewayName
     * @param float $amount
     * @param string $currency
     * @param $billingAgreementId
     * @param string $description
     * @return string
     * @throws PaymentFailedException
     */
    public function chargeBillingAgreement($gatewayName, $amount, $currency, $billingAgreementId, $description = '')
    {
        $config = ConfigService::$paymentGateways['paypal'][$gatewayName];

        if (empty($config)) {
            throw new PaymentFailedException('Gateway ' . $gatewayName . ' is not configured.');
        }

        try {
            $this->paypal->configure($config);

            $transactionId = $this->paypal->createReferenceTransaction(
                $amount,
                $description,
                $billingAgreementId,
                $currency
            );
        } catch (Exception $exception) {
            throw new PaymentFailedException('Payment failed: ' . $exception->getMessage());
        }

        return $transactionId;
    }

    /**
     * @param float $amount
     * @param string $currency
     * @param $transactionId
     * @param string $gatewayName
     * @param string $description
     * @return null|string
     * @throws RefundFailedException
     */
    public function refund($amount, $currency, $transactionId, $gatewayName, $description = '')
    {
        $config = ConfigService::$paymentGateways['paypal'][$gatewayName];

        if (empty($config)) {
            throw new RefundFailedException('Gateway ' . $gatewayName . ' is not configured.');
        }

        try {
            $refundId = $this->paypal->createTransactionRefund(
                $amount,
                true,
                $transactionId,
                $description,
                $currency
            );
        } catch (Exception $exception) {
            throw new RefundFailedException('Payment failed: ' . $exception->getMessage());
        }

        return $refundId;
    }
}