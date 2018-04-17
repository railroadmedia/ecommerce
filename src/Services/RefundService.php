<?php

namespace Railroad\Ecommerce\Services;


use Carbon\Carbon;
use Railroad\Ecommerce\Exceptions\PayPal\CreateRefundException;
use Railroad\Ecommerce\ExternalHelpers\PayPal;
use Railroad\Ecommerce\ExternalHelpers\Stripe;
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
     * @var Stripe
     */
    private $stripeService;

    /**
     * @var PayPal
     */
    private $payPalService;


    /**
     * RefundService constructor.
     * @param RefundRepository $refundRepository
     * @param PaymentRepository $paymentRepository
     */
    public function __construct(
        RefundRepository $refundRepository,
        PaymentRepository $paymentRepository,
        PaymentMethodRepository $paymentMethodRepository,
        PayPal $payPal,
        Stripe $stripe
    ) {
        $this->refundRepository = $refundRepository;
        $this->paymentRepository = $paymentRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->payPalService = $payPal;
        $this->stripeService = $stripe;
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
        $paymentMethod = $this->paymentMethodRepository->getById($payment['payment_method_id']);

        $paymentAmount = $payment['due'];
        $externalProvider = $payment['external_provider'];

        if ($paymentMethod['method_type'] == PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE) {
            $this->stripeService->setApiKey(ConfigService::$stripeAPI[$paymentMethod['method']['config']]['stripe_api_secret']);
            $stripeRefund = $this->stripeService->createRefund($refundedAmount, $payment['external_id'], $note);
            $externalId = $stripeRefund->id;
        } else if ($paymentMethod['method_type'] == PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE) {
            $this->payPalService->setApiKey($paymentMethod['method']['config']);
            try {
                $paypalRefundId = $this->payPalService->createTransactionRefund(
                    $refundedAmount,
                    $refundedAmount != $paymentAmount,
                    $payment['external_id'],
                    $note,
                    $payment['currency']
                );
                $externalId = $paypalRefundId;
            } catch (\Exception $e) {
                throw new CreateRefundException('Paypal refund failed. Message: ' . $e->getMessage());
            }
        }

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