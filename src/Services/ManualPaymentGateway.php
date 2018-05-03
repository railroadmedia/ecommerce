<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;


class ManualPaymentGateway
{

    public function chargePayment($due, $paid, $method, $type, $currency)
    {
        $paymentData = [
            'due'               => $due,
            'paid'              => $paid,
            'type'              => $type,
            'external_provider' => PaymentService::MANUAL_PAYMENT_TYPE,
            'status'            => true,
            'payment_method_id' => null,
            'currency'          => $currency,
            'created_on'        => Carbon::now()->toDateTimeString()
        ];

        return $paymentData;
    }
}