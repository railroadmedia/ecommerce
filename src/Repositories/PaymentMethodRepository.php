<?php

namespace Railroad\Ecommerce\Repositories;


use Illuminate\Database\Query\Builder;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;

class PaymentMethodRepository extends RepositoryBase
{

    /**
     * @var CreditCardRepository
     */
    private $creditCardRepository;

    /**
     * @var PaypalBillingAgreementRepository
     */
    private $paypalBillingAgreementRepository;

    public static $pullAllPaymentMethods = false;

    /**
     * PaymentMethodRepository constructor.
     * @param $creditCardRepository
     */
    public function __construct(CreditCardRepository $creditCardRepository, PaypalBillingAgreementRepository $paypalBillingAgreementRepository)
    {
        parent::__construct();

        $this->creditCardRepository = $creditCardRepository;
        $this->paypalBillingAgreementRepository = $paypalBillingAgreementRepository;
    }

    /**
     * @return Builder
     */
    protected function query()
    {
        return $this->connection()->table(ConfigService::$tablePaymentMethod);
    }

    public function getById($id)
    {
        $query = $this->query()
            ->where([ConfigService::$tablePaymentMethod . '.id' => $id]);

        $paymentMethod = $query->get()
            ->first();

        if (empty($paymentMethod)) {
            return null;
        }

        if ($paymentMethod['method_type'] == PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE) {
            $creditCard = $this->creditCardRepository->getById($paymentMethod['method_id']);
            $paymentMethod['method'] = $creditCard;

        } else if ($paymentMethod['method_type'] == PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE) {
            $paypalBillingAgreement = $this->paypalBillingAgreementRepository->getById($paymentMethod['method_id']);
            $paymentMethod['method'] = $paypalBillingAgreement;
        }
        unset($paymentMethod['method_id']);
        return $paymentMethod;
    }
}