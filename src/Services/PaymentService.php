<?php

namespace Railroad\Ecommerce\Services;

use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Repositories\CreditCardRepository;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\PaypalBillingAgreementRepository;

/**
 * todo: needs testing
 *
 * Class PaymentService
 * @package Railroad\Ecommerce\Services
 */
class PaymentService
{
    /**
     * @var PaymentMethodRepository
     */
    private $paymentMethodRepository;

    /**
     * @var CreditCardRepository
     */
    private $creditCardRepository;

    /**
     * @var StripePaymentGateway
     */
    private $stripePaymentGateway;

    /**
     * @var PaypalBillingAgreementRepository
     */
    private $paypalBillingAgreementRepository;

    /**
     * @var PayPalPaymentGateway
     */
    private $payPalPaymentGateway;

    /**
     * @var CurrencyService
     */
    private $currencyService;

    /**
     * PaymentService constructor.
     *
     * @param PaymentMethodRepository $paymentMethodRepository
     * @param CreditCardRepository $creditCardRepository
     * @param StripePaymentGateway $stripePaymentGateway
     * @param PaypalBillingAgreementRepository $paypalBillingAgreementRepository
     * @param PayPalPaymentGateway $payPalPaymentGateway
     * @param CurrencyService $currencyService
     */
    public function __construct(
        PaymentMethodRepository $paymentMethodRepository,
        CreditCardRepository $creditCardRepository,
        StripePaymentGateway $stripePaymentGateway,
        PaypalBillingAgreementRepository $paypalBillingAgreementRepository,
        PayPalPaymentGateway $payPalPaymentGateway,
        CurrencyService $currencyService
    )
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->creditCardRepository = $creditCardRepository;
        $this->stripePaymentGateway = $stripePaymentGateway;
        $this->paypalBillingAgreementRepository = $paypalBillingAgreementRepository;
        $this->payPalPaymentGateway = $payPalPaymentGateway;
        $this->currencyService = $currencyService;
    }

    /**
     * @param string $gateway
     * @param int $paymentMethodId
     * @param string $currency
     * @param float $paymentAmount
     * @param int $userId
     *
     * @return string - Returns the external payment id.
     *
     * @throws PaymentFailedException
     */
    public function chargeUsersExistingPaymentMethod(
        string $gateway,
        int $paymentMethodId,
        string $currency,
        float $paymentAmount,
        int $userId
    )
    {
        $paymentMethod = $this->paymentMethodRepository->getUsersPaymentMethodById($userId, $paymentMethodId);

        if (empty($paymentMethod)) {
            throw new PaymentFailedException('Invalid Payment Method');
        }

        $externalPaymentId = null;

        // credit cart
        if ($paymentMethod->getMethodType() == PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE) {
            $externalPaymentId =
                $this->chargeCreditCardPaymentMethod($paymentMethod, $paymentAmount, $currency, $gateway);
        }

        // paypal
        elseif ($paymentMethod->getMethodType() == PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE) {
            $externalPaymentId = $this->chargePayPalPaymentMethod($paymentMethod, $paymentAmount, $currency, $gateway);
        }

        // failure
        else {
            throw new PaymentFailedException('Invalid payment method.');
        }

        // payment failed
        if (empty($externalPaymentId)) {
            throw new PaymentFailedException('Could not recharge existing payment method.');
        }

        return $externalPaymentId;
    }

    /**
     * Re-charge an existing credit card payment method.
     *
     * @param PaymentMethod $paymentMethod
     * @param float $amount
     * @param string $currency
     * @param string $gateway
     *
     * @return string|null - Returns the external payment id.
     *
     * @throws PaymentFailedException
     */
    private function chargeCreditCardPaymentMethod(
        PaymentMethod $paymentMethod,
        float $amount,
        string $currency,
        string $gateway
    )
    {
        $creditCard = $this->creditCardRepository->find($paymentMethod->getMethodId());

        $customer = $this->stripePaymentGateway->getCustomer($gateway, $creditCard->getExternalCustomerId());

        if (!$customer) {
            return null;
        }

        $card = $this->stripePaymentGateway->getCard($customer, $creditCard->getExternalId(), $gateway);

        if (!$card) {
            return null;
        }

        $convertedPrice = $this->currencyService->convertFromBase($amount, $currency);

        $charge = $this->stripePaymentGateway->chargeCustomerCard(
            $gateway,
            $convertedPrice,
            $currency,
            $card,
            $customer
        );

        return $charge->id;
    }

    /**
     * Re-charge an existing paypal agreement payment method.
     *
     * @param PaymentMethod $paymentMethod
     * @param float $amount
     * @param string $currency
     * @param string $gateway
     *
     * @return string|null - Returns the external payment id.
     *
     * @throws PaymentFailedException
     */
    private function chargePayPalPaymentMethod(
        PaymentMethod $paymentMethod,
        float $amount,
        string $currency,
        string $gateway
    )
    {
        $payPalAgreement = $this->paypalBillingAgreementRepository->find($paymentMethod->getMethodId());

        $convertedPrice = $this->currencyService->convertFromBase($amount, $currency);

        return $this->payPalPaymentGateway->chargeBillingAgreement(
            $gateway,
            $convertedPrice,
            $currency,
            $payPalAgreement->getExternalId()
        );
    }
}