<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Exceptions\PayPal\CreateBillingAgreementException;
use Railroad\Ecommerce\Exceptions\PayPal\CreateReferenceTransactionException;
use Railroad\Ecommerce\ExternalHelpers\PayPal;
use Railroad\Ecommerce\Repositories\PaymentGatewayRepository;


class PaypalPaymentGateway
{
    /**
     * @var PayPal
     */
    private $payPalService;

    /**
     * @var \Railroad\Ecommerce\Repositories\PaymentGatewayRepository
     */
    private $paymentGatewayRepository;

    /**
     * PaypalPaymentGateway constructor.
     *
     * @param PayPal $payPalService
     */
    public function __construct(
        PayPal $payPalService,
        PaymentGatewayRepository $paymentGatewayRepository
    ) {
        $this->payPalService            = $payPalService;
        $this->paymentGatewayRepository = $paymentGatewayRepository;
    }

    public function chargePayment($due, $paid, $paymentMethod, $type, $currency)
    {
        $paymentData = [
            'due'               => $due,
            'type'              => $type,
            'payment_method_id' => $paymentMethod['id'],
            'created_on'        => Carbon::now()->toDateTimeString()
        ];

        try
        {
            $paymentData['external_id']       = $this->chargePayPalReferenceAgreementPayment($due, $paymentMethod);
            $paymentData['paid']              = $due;
            $paymentData['external_provider'] = 'paypal';
            $paymentData['status']            = true;
            $paymentData['currency']          = $currency;
        }
        catch(Exception $e)
        {
            $paymentData['paid']              = 0;
            $paymentData['status']            = false;
            $paymentData['external_provider'] = 'paypal';
            $paymentData['message']           = $e->getMessage();
        }

        return $paymentData;
    }

    /**
     * @param               $amount
     * @param array         $paymentMethod
     * @return string
     * @throws PaymentErrorException
     * @throws PaymentFailedException
     */
    public function chargePayPalReferenceAgreementPayment(
        $due,
        $paymentMethod
    ) {
        if(empty($paymentMethod['method']['agreement_id']))
        {
            throw new NotFoundException(
                'Payment failed due to an internal error. Please contact support.', 4000
            );
        }

        try
        {
            $paymentGateway = $this->paymentGatewayRepository->getById($paymentMethod['method']['payment_gateway_id']);
            $this->payPalService->setApiKey($paymentGateway['config']);

            $payPalTransactionId = $this->payPalService->createReferenceTransaction(
                $due,
                '',
                $paymentMethod['method']['agreement_id'],
                $paymentMethod['currency']
            );
        }
        catch(CreateReferenceTransactionException $cardException)
        {
            throw new NotFoundException(
                'Payment failed. Please make sure your PayPal account is properly funded.'
            );
        }

        return $payPalTransactionId;
    }

    /** Create paypal billing record and return the id
     *
     * @param $agreementId
     * @param $expressCheckoutToken
     * @param $addressId
     * @return int
     */
    public function createPaypalBilling($expressCheckoutToken, $paymentGateway)
    {
        $this->payPalService->setApiKey($paymentGateway['config']);
        try
        {
            $agreementId = $this->payPalService->confirmAndCreateBillingAgreement($expressCheckoutToken);
        }
        catch(CreateBillingAgreementException $e)
        {
            return null;
        }

        return $agreementId;
    }

    /** Create a paypal billing agreement id based on express checkout token
     *
     * @param array $data
     * @return array
     */
    public function handlingData(array $data)
    {
        return [
            'billingAgreementId' => $this->createPaypalBilling($data['expressCheckoutToken'], $data['paymentGateway'])
        ];
    }
}