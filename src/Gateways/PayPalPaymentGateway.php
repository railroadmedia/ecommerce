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
    public function createBillingAgreement($gatewayName, $amount, $currency, $expressCheckoutToken, $description = '')
    {
        $config = ConfigService::$paymentGateways['paypal'][$gatewayName];

        if (empty($config)) {
            throw new PaymentFailedException('Gateway ' . $gatewayName . ' is not configured.');
        }

        try {
            $this->paypal->configure($config);

            $billingAgreementId = $this->paypal->confirmAndCreateBillingAgreement($expressCheckoutToken);
        } catch (Exception $exception) {
            error_log($exception->getMessage());

            throw new PaymentFailedException(
                'Payment failed. Please ensure your PayPal account is funded and has a linked credit card then try again.'
            );
        }

        return $billingAgreementId;
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
     * @param $returnUrl
     * @return mixed
     * @throws PaymentFailedException
     */
    public function getBillingAgreementExpressCheckoutUrl($gatewayName, $returnUrl)
    {
        $config = ConfigService::$paymentGateways['paypal'][$gatewayName];

        if (empty($config)) {
            throw new PaymentFailedException('Gateway ' . $gatewayName . ' is not configured.');
        }

        try {
            $this->paypal->configure($config);

            return $config['paypal_api_checkout_redirect_url'] .
                $this->paypal->createBillingAgreementExpressCheckoutToken(
                    $returnUrl,
                    $returnUrl
                );
        } catch (Exception $exception) {
            throw new PaymentFailedException('Payment failed: ' . $exception->getMessage());
        }
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
            $this->paypal->configure($config);

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