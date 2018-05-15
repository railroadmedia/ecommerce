<?php

namespace Railroad\Ecommerce\Repositories;

use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Resora\Queries\CachedQuery;
use Railroad\Resora\Repositories\RepositoryBase;

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

    /**
     * @var UserPaymentMethodsRepository
     */
    private $userPaymentMethodsRepository;

    /**
     * @var CustomerPaymentMethodsRepository
     */
    private $customerPaymentMethodsRepository;

    /**
     * PaymentMethodRepository constructor.
     *
     * @param CreditCardRepository $creditCardRepository
     * @param PaypalBillingAgreementRepository $paypalBillingAgreementRepository
     */
    public function __construct(
        CreditCardRepository $creditCardRepository,
        PaypalBillingAgreementRepository $paypalBillingAgreementRepository,
        CustomerPaymentMethodsRepository $customerPaymentMethodsRepository,
        UserPaymentMethodsRepository $userPaymentMethodsRepository
    ) {
        //  parent::__construct();

        $this->creditCardRepository = $creditCardRepository;
        $this->paypalBillingAgreementRepository = $paypalBillingAgreementRepository;
        $this->customerPaymentMethodsRepository = $customerPaymentMethodsRepository;
        $this->userPaymentMethodsRepository = $userPaymentMethodsRepository;
    }

    /**
     * @return CachedQuery|$this
     */
    protected function newQuery()
    {
        return (new CachedQuery($this->connection()))->from(ConfigService::$tablePaymentMethod);
    }

    /** Get payment method by id
     *
     * @param int $id
     * @return array|mixed|null
     */
    public function getById($id)
    {
        $paymentMethod = $this->query()
            ->joinUserAndCustomerTables()
            ->selectColumns()
            ->where([ConfigService::$tablePaymentMethod . '.id' => $id])
            ->get()
            ->first();

        if (empty($paymentMethod)) {
            return null;
        }

        if ($paymentMethod['method_type'] == PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE) {
            $paymentMethod['method'] = $this->creditCardRepository->getById($paymentMethod['method_id']);
        } else {
            if ($paymentMethod['method_type'] == PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE) {
                $paymentMethod['method'] =
                    $this->paypalBillingAgreementRepository->getById($paymentMethod['method_id']);
            }
        }

        unset($paymentMethod['method_id']);

        return $paymentMethod;
    }
}