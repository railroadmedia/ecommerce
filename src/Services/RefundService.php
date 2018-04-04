<?php

namespace Railroad\Ecommerce\Services;


use Carbon\Carbon;
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
     * RefundService constructor.
     * @param RefundRepository $refundRepository
     * @param PaymentRepository $paymentRepository
     */
    public function __construct(
        RefundRepository $refundRepository,
        PaymentRepository $paymentRepository
    ) {
        $this->refundRepository = $refundRepository;
        $this->paymentRepository = $paymentRepository;
    }


    /** Call the method that save the refund in the database, update the refund value for the payment and return an array with the new created refund
     * @param integer $paymentId
     * @param integer $refundedAmount
     * @param string $note
     * @return array
     */
    public function store($paymentId, $refundedAmount, $note)
    {
        $payment = $this->paymentRepository->getById($paymentId);

        $paymentAmount = $payment['due'];
        $externalProvider = $payment['external_provider'];
        $externalId = $payment['external_id'];

        $refundId = $this->refundRepository->create([
            'payment_id' => $paymentId,
            'payment_amount' => $paymentAmount,
            'refunded_amount' => $refundedAmount,
            'note' => $note,
            'external_provider' => $externalProvider,
            'external_id' => $externalId,
            'created_on' => Carbon::now()->toDateTimeString()
        ]);

        //update payment refund value
        $this->paymentRepository->update($paymentId, [
            'refunded' => $payment['refunded'] + $refundedAmount,
            'updated_on' => Carbon::now()->toDateTimeString()
        ]);

        return $this->getById($refundId);
    }

    /** Get the refund based on the id
     * @param integer $id
     * @return array
     */
    public function getById($id)
    {
        return $this->refundRepository->getById($id);
    }


}