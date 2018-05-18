<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Exceptions\PayPal\CreateBillingAgreementException;
use Railroad\Ecommerce\Exceptions\PayPal\CreateReferenceTransactionException;
use Railroad\Ecommerce\ExternalHelpers\PayPal;
use Railroad\Ecommerce\Repositories\PaymentGatewayRepository;
use Railroad\Ecommerce\Repositories\PaypalBillingAgreementRepository;

class PaypalPaymentGateway
{
    /**
     * @var PayPal
     */
    private $payPalService;

    /**
     * @var PaymentGatewayRepository
     */
    private $paymentGatewayRepository;

    /**
     * @var PaypalBillingAgreementRepository
     */
    private $paypalBillingAgreementRepository;

    /**
     * PaypalPaymentGateway constructor.
     *
     * @param PayPal $payPalService
     */
    public function __construct(
        PayPal $payPalService,
        PaymentGatewayRepository $paymentGatewayRepository,
        PaypalBillingAgreementRepository $paypalBillingAgreementRepository
    ) {
        $this->payPalService                    = $payPalService;
        $this->paymentGatewayRepository         = $paymentGatewayRepository;
        $this->paypalBillingAgreementRepository = $paypalBillingAgreementRepository;
    }

    public function chargePayment($due, $paid, $paymentMethod, $currency)
    {
        $paymentData = [
            'due'               => $due,
            'payment_method_id' => $paymentMethod['id'],
            'created_on'        => Carbon::now()->toDateTimeString()
        ];

        try
        {
            $charge                           = $this->chargePayPalReferenceAgreementPayment($due, $paymentMethod, $currency);
            $paymentData['external_id']       = $charge['results'];
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
     * @return arry
     */
    public function chargePayPalReferenceAgreementPayment(
        $due,
        $paymentMethod,
        $currency
    ) {
        if(empty($paymentMethod['method']['agreement_id']))
        {
            return
                [
                    'status'  => false,
                    'message' => 'Payment failed due to an internal error:: empty agreement id.'
                ];
        }

        try
        {
            $paymentGateway = $this->paymentGatewayRepository->getById($paymentMethod['method']['payment_gateway_id']);
            $this->payPalService->setApiKey($paymentGateway['config']);

            $payPalTransactionId = $this->payPalService->createReferenceTransaction(
                $due,
                '',
                $paymentMethod['method']['agreement_id'],
                $currency
            );
        }
        catch(CreateReferenceTransactionException $cardException)
        {
            return
                [
                    'status'  => false,
                    'message' => 'Payment failed. Please make sure your PayPal account is properly funded::' . $cardException->getMessage()
                ];
        }

        return [
            'status'  => true,
            'results' => $payPalTransactionId
        ];
    }

    /** Create paypal billing record and return the id
     *
     * @param $agreementId
     * @param $expressCheckoutToken
     * @param $addressId
     * @return int
     */
    public function saveExternalData(array $data)
    {
        $paymentGateway = $this->paymentGatewayRepository->read($data['paymentGateway']);
        $this->payPalService->setApiKey($paymentGateway['config']);
        try
        {
            $agreementId = $this->payPalService->confirmAndCreateBillingAgreement($data['expressCheckoutToken']);
        }
        catch(CreateBillingAgreementException $e)
        {
            return
                [
                    'status'  => false,
                    'message' => 'Paypal billing agreement creation failed. ::' . $e->getMessage()
                ];
        }

        $paypalBillingAgreement = $this->paypalBillingAgreementRepository->create([
            'agreement_id'           => $agreementId,
            'express_checkout_token' => $data['expressCheckoutToken'],
            'address_id'             => $data['address_id'],
            'payment_gateway_id'     => $data['paymentGateway'],
            'expiration_date'        => Carbon::now()->addYears(10),
            'created_on'             => Carbon::now()->toDateTimeString()
        ]);

        return array_merge($paypalBillingAgreement,
            [
                'status'  => true,
                'results' => $agreementId
            ]);
    }

    /** Create a paypal billing agreement id based on express checkout token
     *
     * @param array $data
     * @return array
     */
    public function handlingData(array $data)
    {
        return $this->createPaypalBilling($data['expressCheckoutToken'], $data['paymentGateway'], $data['address_id']);
    }

    /** Create a new Paypal refund transaction.
     *Return the external ID for the refund action or NULL there are exception
     *
     * @param string  $paymentConfig
     * @param integer $refundedAmount
     * @param integer $paymentAmount
     * @param string  $currency
     * @param integer $paymentExternalId
     * @param string  $note
     * @return null|integer
     */
    public function refund($paymentConfig, $refundedAmount, $paymentAmount, $currency, $paymentExternalId, $note)
    {
        $this->payPalService->setApiKey($paymentConfig);
        try
        {
            $paypalRefundId = $this->payPalService->createTransactionRefund(
                $refundedAmount,
                $refundedAmount != $paymentAmount,
                $paymentExternalId,
                $note,
                $currency
            );

            return $paypalRefundId;
        }
        catch(\Exception $e)
        {
            return null;
        }
    }

    public function deleteMethod($agreementId)
    {
        return $this->paypalBillingAgreementRepository->destroy($agreementId);
    }
}