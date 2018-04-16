<?php


namespace Railroad\Ecommerce\Services;


use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\CreditCardRepository;
use Railroad\Ecommerce\Repositories\PaymentGatewayRepository;
use Railroad\Ecommerce\Repositories\PaypalBillingAgreementRepository;

class PaymentGatewayService
{
    /**
     * @var PaymentGatewayRepository
     */
    private $paymentGatewayRepository;

    /**
     * @var CreditCardRepository
     */
    private $creditCardRepository;

    /**
     * @var PaypalBillingAgreementRepository
     */
    private $billingAgreementRepository;

    /**
     * PaymentGatewayService constructor.
     * @param PaymentGatewayRepository $paymentGatewayRepository
     */
    public function __construct(PaymentGatewayRepository $paymentGatewayRepository,
                                CreditCardRepository $creditCardRepository, PaypalBillingAgreementRepository $paypalBillingAgreementRepository)
    {
        $this->paymentGatewayRepository = $paymentGatewayRepository;
        $this->creditCardRepository = $creditCardRepository;
        $this->billingAgreementRepository = $paypalBillingAgreementRepository;
    }


    /** Create a new payment gateway and return an array with the new created payment gateway data
     * @param string $brand
     * @param string $type
     * @param string $name
     * @param string $configName
     * @return array
     */
    public function store($brand, $type, $name, $configName)
    {
        $paymentGatewayId = $this->paymentGatewayRepository->create([
            'brand' => $brand ?? ConfigService::$brand,
            'type' => $type,
            'name' => $name,
            'config' => $configName,
            'created_on' => Carbon::now()->toDateTimeString()
        ]);

        return $this->getById($paymentGatewayId);
    }

    /** Return an array with payment gateway data based on payment gateway id
     * @param integer $id
     * @return array
     */
    public function getById($id)
    {
        return $this->paymentGatewayRepository->getById($id);
    }

    /** Update and return the modified payment gateway. If the payment gateway not exist return null.
     * @param integer $id
     * @param array $data
     * @return array|null
     */
    public function update($id, array $data)
    {
        $paymentGateway = $this->getById($id);

        if (empty($paymentGateway)) {
            return null;
        }

        $data['updated_on'] = Carbon::now()->toDateTimeString();
        $this->paymentGatewayRepository->update($id, $data);

        return $this->getById($id);
    }

    /** Delete a payment gateway if it's not connected to payment method.
     *  Return - null if the payment method not exists
     *         - 0 if the payment gateway it's connected with payment methods (credit cards or billing agreements)
     *          - true if the payment gateway was deleted
     * @param integer $ppaymentGatewayId
     * @return bool|int|null
     */
    public function delete($paymentGatewayId)
    {
        $paymentGateway = $this->getById($paymentGatewayId);

        if (empty($paymentGateway)) {
            return null;
        }

        $creditCards = $this->creditCardRepository->getByPaymentGatewayId($paymentGatewayId);

        if (count($creditCards) > 0) {
            return 0;
        }

        $billingAgreement = $this->billingAgreementRepository->getByPaymentGatewayId($paymentGatewayId);

        if (count($billingAgreement) > 0) {
            return 0;
        }

        return $this->paymentGatewayRepository->delete($paymentGatewayId);
    }


}