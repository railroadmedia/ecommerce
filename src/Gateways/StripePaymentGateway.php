<?php

namespace Railroad\Ecommerce\Gateways;

use Exception;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\ExternalHelpers\Stripe;
use Railroad\Ecommerce\Services\ConfigService;

class StripePaymentGateway
{
    /**
     * @var Stripe
     */
    protected $stripe;

    /**
     * StripePaymentGateway constructor.
     *
     * @param Stripe $stripe
     */
    public function __construct(Stripe $stripe)
    {
        $this->stripe = $stripe;
    }

    /**
     * @param $gatewayName
     * @param $amount
     * @param $currency
     * @param $tokenId
     * @param string $description
     * @return mixed
     * @throws PaymentFailedException
     */
    public function chargeToken($gatewayName, $amount, $currency, $tokenId, $description = '')
    {
        $config = ConfigService::$paymentGateways['stripe'][$gatewayName];

        if (empty($config)) {
            throw new PaymentFailedException('Gateway ' . $gatewayName . ' is not configured.');
        }

        try {
            $this->stripe->setApiKey($config['stripe_api_secret']);

            $source = $this->stripe->retrieveToken($tokenId);

            $charge = $this->stripe->createCharge(
                $amount * 100,
                $source,
                $currency,
                $description
            );
        } catch (Exception $exception) {
            throw new PaymentFailedException('Payment failed: ' . $exception->getMessage());
        }

        return $charge['id'];
    }

    /**
     * @param $amount
     * @param $currency
     * @param $cardId
     * @param $customerId
     * @param $gatewayName
     * @param string $description
     * @return string
     * @throws PaymentFailedException
     */
    public function chargeCustomerCard(
        $amount,
        $currency,
        $cardId,
        $customerId,
        $gatewayName,
        $description = ''
    ) {
        $config = ConfigService::$paymentGateways['stripe'][$gatewayName];

        if (empty($config)) {
            throw new PaymentFailedException('Gateway ' . $gatewayName . ' is not configured.');
        }

        try {
            $this->stripe->setApiKey($config['stripe_api_secret']);

            $customer = $this->stripe->retrieveCustomer($customerId);
            $card = $customer->sources->retrieve($cardId);

            $charge = $this->stripe->createCharge(
                $amount * 100,
                $card,
                $currency,
                $description
            );
        } catch (Exception $exception) {
            throw new PaymentFailedException('Payment failed: ' . $exception->getMessage());
        }

        return $charge['id'];
    }

    /**
     * @param $amount
     * @param $externalPaymentId
     * @param $reason
     * @param $gatewayName
     * @return string
     * @throws PaymentFailedException
     */
    public function refund($gatewayName, $amount, $externalPaymentId, $reason = null)
    {
        $config = ConfigService::$paymentGateways['stripe'][$gatewayName];

        if (empty($config)) {
            throw new PaymentFailedException('Gateway ' . $gatewayName . ' is not configured.');
        }

        if (!in_array($reason, ['duplicate', 'fraudulent', 'requested_by_customer'])) {
            $reason = null;
        }

        try {
            $this->stripe->setApiKey($config['stripe_api_secret']);

            $refund = $this->stripe->createRefund($amount * 100, $externalPaymentId, $reason);
        } catch (Exception $exception) {
            throw new PaymentFailedException('Payment failed: ' . $exception->getMessage());
        }

        return $refund['id'];
    }
}