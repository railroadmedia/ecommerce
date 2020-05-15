<?php

namespace Railroad\Ecommerce\ExternalHelpers;

use Railroad\Ecommerce\Exceptions\PayPal\CreateBillingAgreementException;
use Railroad\Ecommerce\Exceptions\PayPal\CreateReferenceTransactionException;
use Railroad\Ecommerce\Exceptions\PayPal\CreateRefundException;
use Railroad\Ecommerce\Exceptions\PayPal\DoExpressCheckoutException;
use Railroad\Ecommerce\Exceptions\PayPal\ManageRecurringPaymentsProfileStatusException;

class PayPal
{
    private $apiUsername;
    private $apiPassword;
    private $apiSignature;
    private $apiCurrencyCode;
    private $apiVersion;
    private $apiNVPCurlUrl;
    public $expressCheckoutUrlPrefix;

    const EXTERNAL_PROVIDER_KEY = 'paypal';

    /**
     * @param string $returnUrl
     * @param string $cancelUrl
     * @return string
     * @throws DoExpressCheckoutException
     */
    public function createBillingAgreementExpressCheckoutToken($returnUrl, $cancelUrl)
    {
        $nvp =
            '&RETURNURL=' .
            urlencode($returnUrl) .
            '&CANCELURL=' .
            urlencode($cancelUrl) .
            '&AMT=' .
            0 .
            '&PAYMENTREQUEST_0_PAYMENTACTION=Sale' .
            '&BILLINGTYPE=MerchantInitiatedBilling' .
            '&NOSHIPPING=1';

        $response = $this->sendRequest('SetExpressCheckout', $nvp);

        if ($this->payPalResponseFailed($response) || empty($response['TOKEN'])) {
            throw new DoExpressCheckoutException(
                'PayPal Response: ' . var_export($response, true) . ', NVPSTR: ' . $nvp
            );
        }

        return $response['TOKEN'];
    }

    /**
     * @param string $expressCheckoutToken
     * @return string
     * @throws CreateBillingAgreementException
     */
    public function confirmAndCreateBillingAgreement($expressCheckoutToken)
    {
        $nvp = '&TOKEN=' . $expressCheckoutToken;

        $response = $this->sendRequest('CreateBillingAgreement', $nvp);

        if ($this->payPalResponseFailed($response) || empty($response['BILLINGAGREEMENTID'])) {
            throw new CreateBillingAgreementException(
                'PayPal Response: ' . var_export($response, true) . ', NVPSTR: ' . $nvp
            );
        }

        return $response['BILLINGAGREEMENTID'];
    }

    /**
     * @param float $totalAmount
     * @param string $paymentDescription
     * @param string $billingAgreementId
     * @param string $currency
     *
     * @return string
     *
     * @throws CreateReferenceTransactionException
     */
    public function createReferenceTransaction(
        $totalAmount,
        $paymentDescription,
        $billingAgreementId,
        $currency = 'USD'
    )
    {
        $nvp =
            '&AMT=' .
            $totalAmount .
            '&DESC=' .
            $paymentDescription .
            '&REFERENCEID=' .
            $billingAgreementId .
            '&RECURRING=true' .
            '&PAYMENTTYPE=InstantOnly' .
            '&PAYMENTACTION=Sale' .
            '&CURRENCYCODE=' .
            $currency;

        $response = $this->sendRequest('DoReferenceTransaction', $nvp);

        if ($this->payPalResponseFailed($response) || strtolower($response['PAYMENTSTATUS']) != 'completed') {
            error_log(var_export($response, true));
            throw new CreateReferenceTransactionException(
                'PayPal Response: ' . var_export($response, true) . ', NVPSTR: ' . $nvp
            );
        }

        return $response['TRANSACTIONID'];
    }

    /**
     * @param float $amountToRefund
     * @param boolean $isPartialRefund
     * @param string $transactionId
     * @param string $reason
     * @param string $currency
     *
     * @return string
     *
     * @throws CreateRefundException
     */
    public function createTransactionRefund(
        $amountToRefund,
        $isPartialRefund,
        $transactionId,
        $reason,
        $currency = 'USD'
    )
    {
        $nvp =
            '&AMT=' .
            $amountToRefund .
            '&TRANSACTIONID=' .
            $transactionId .
            '&REFUNDTYPE=' .
            ($isPartialRefund ? 'Partial' : 'Full') .
            '&NOTE=' .
            $reason .
            '&PAYMENTTYPE=InstantOnly' .
            '&CURRENCYCODE=' .
            $currency;

        $response = $this->sendRequest('RefundTransaction', $nvp);

        if ($this->payPalResponseFailed($response) || strtolower($response['REFUNDSTATUS']) == 'none') {
            throw new CreateRefundException(
                'PayPal Response: ' . var_export($response, true) . ', NVPSTR: ' . $nvp
            );
        }

        return $response['REFUNDTRANSACTIONID'];
    }

    /**
     * @param $profileId
     * @return array
     */
    public function getRecurringPaymentProfileDetails($profileId)
    {
        $nvp = '&PROFILEID=' . $profileId;

        return $this->sendRequest('GetRecurringPaymentsProfileDetails', $nvp);
    }

    public function sendRequest($method, $nvp)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->apiNVPCurlUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        $nvpreq =
            'METHOD=' .
            urlencode($method) .
            '&VERSION=' .
            urlencode($this->apiVersion) .
            '&USER=' .
            urlencode($this->apiUsername) .
            '&PWD=' .
            urlencode($this->apiPassword) .
            '&SIGNATURE=' .
            urlencode($this->apiSignature) .
            $nvp;

        curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

        $response = array();

        parse_str(curl_exec($ch), $response);

        return $response;
    }

    public function payPalResponseFailed($response)
    {
        if (!empty($response['ACK']) &&
            (strtolower($response['ACK']) == 'success' || strtolower($response['ACK']) == 'successwithwarning')) {
            error_log(var_export($response, true));

            return false;
        }

        return true;
    }

    public function respondToIpnRequest()
    {
        $raw_post_data = file_get_contents('php://input');
        $raw_post_array = explode('&', $raw_post_data);
        $myPost = array();

        foreach ($raw_post_array as $keyval) {
            $keyval = explode('=', $keyval);
            if (count($keyval) == 2) {
                $myPost[$keyval[0]] = urldecode($keyval[1]);
            }
        }

        $req = 'cmd=_notify-validate';

        if (function_exists('get_magic_quotes_gpc')) {
            $get_magic_quotes_exists = true;
        }

        foreach ($myPost as $key => $value) {
            if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
                $value = urlencode(stripslashes($value));
            }
            else {
                $value = urlencode($value);
            }

            $req .= "&$key=$value";
        }

        $paypal_url = "https://www.paypal.com/cgi-bin/webscr";
        $ch = curl_init($paypal_url);

        if ($ch == false) {
            return false;
        }

        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));

        $res = curl_exec($ch);

        if (curl_errno($ch) != 0 || $res != 'VERIFIED') // cURL error
        {
            error_log(
                date('[Y-m-d H:i e] ') . "Can't connect to PayPal to validate IPN message: " . curl_error(
                    $ch
                ) . PHP_EOL
            );
            curl_close($ch);
            return false;
        }
        else {
            // Log the entire HTTP response if debug is switched on.
            curl_close($ch);
        }

        return true;
    }

    public function setRecurringProfileStatus($recurringProfileId, $status, $note = '')
    {
        $nvpstr = '&PROFILEID=' . $recurringProfileId . '&ACTION=' . $status . '&NOTE=' . $note;

        $payPalResponse = $this->sendRequest(
            'ManageRecurringPaymentsProfileStatus',
            $nvpstr
        );

        if ($payPalResponse['PROFILEID'] != $recurringProfileId) {
            throw new ManageRecurringPaymentsProfileStatusException(
                'PayPal Response: ' . var_export($payPalResponse, true) . ', NVPSTR: ' . $nvpstr
            );
        }
    }

    public function configure($details)
    {
        $this->apiUsername = $details['paypal_api_username'];
        $this->apiPassword = $details['paypal_api_password'];
        $this->apiSignature = $details['paypal_api_signature'];
        $this->apiCurrencyCode = $details['paypal_api_currency_code'];
        $this->apiVersion = $details['paypal_api_version'];
        $this->apiNVPCurlUrl = $details['paypal_api_nvp_curl_url'];
        $this->expressCheckoutUrlPrefix = $details['paypal_api_checkout_redirect_url'];

        return $this;
    }
}
