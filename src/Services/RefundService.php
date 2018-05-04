<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Factories\GatewayFactory;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\RefundRepository;

class RefundService
{
    /**
     * @var RefundRepository
     */
    private $refundRepository;

    /**
     * @var PaymentRepository
     */
    private $paymentRepository;

    /**
     * @var PaymentMethodRepository
     */
    private $paymentMethodRepository;

    /**
     * @var \Railroad\Ecommerce\Factories\GatewayFactory
     */
    private $gatewayFactory;

    /**
     * RefundService constructor.
     *
     * @param RefundRepository  $refundRepository
     * @param PaymentRepository $paymentRepository
     */
    public function __construct(
        RefundRepository $refundRepository,
        PaymentRepository $paymentRepository,
        PaymentMethodRepository $paymentMethodRepository,
        GatewayFactory $gatewayFactory
    ) {
        $this->refundRepository        = $refundRepository;
        $this->paymentRepository       = $paymentRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->gatewayFactory          = $gatewayFactory;
    }

    /** Call the method that save the refund in the database, update the refund value for the payment and return an array with the new created refund
     *
     * @param integer $paymentId
     * @param integer $refundedAmount
     * @param string  $note
     * @return array
     */
    public function store($paymentId, $refundedAmount, $note)
    {
        $payment       = $this->paymentRepository->getById($paymentId);
        $paymentMethod = $this->paymentMethodRepository->getById($payment['payment_method_id']);
        $paymentAmount    = $payment['due'];
        $externalProvider = $payment['external_provider'];

        $gateway          = $this->gatewayFactory->create($paymentMethod['method_type']);

        $refundExternalId = $gateway->refund(
            $paymentMethod['method']['config'],
            $refundedAmount,
            $paymentAmount,
            $payment['currency'],
            $payment['external_id'],
            $note
        );

        if(!$refundExternalId){
            return null;
        }

        $refundId = $this->refundRepository->create([
            'payment_id'        => $paymentId,
            'payment_amount'    => $paymentAmount,
            'refunded_amount'   => $refundedAmount,
            'note'              => $note,
            'external_provider' => $externalProvider,
            'external_id'       => $refundExternalId,
            'created_on'        => Carbon::now()->toDateTimeString()
        ]);

        //update payment refund value
        $this->paymentRepository->update($paymentId, [
            'refunded'   => $payment['refunded'] + $refundedAmount,
            'updated_on' => Carbon::now()->toDateTimeString()
        ]);

        return $this->getById($refundId);
    }

    /** Get the refund based on the id
     *
     * @param integer $id
     * @return array
     */
    public function getById($id)
    {
        return $this->refundRepository->getById($id);
    }
}