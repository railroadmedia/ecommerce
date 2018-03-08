<?php

namespace Railroad\Ecommerce\Repositories;


use Illuminate\Database\Query\Builder;
use Railroad\Ecommerce\Repositories\QueryBuilders\PaymentMethodQueryBuilder;
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

    /**
     * @var UserPaymentMethodsRepository
     */
    private $userPaymentMethodsRepository;

    /**
     * @var CustomerPaymentMethodsRepository
     */
    private $customerPaymentMethodsRepository;

    public static $pullAllPaymentMethods = false;
    /**
     * If this is false any payment method will be pulled. If its defined, only user payment method will be pulled.
     *
     * @var integer|bool
     */
    public static $availableUserId = false;

    /**
     * If this is false any payment method will be pulled. If its defined, only customer payment method will be pulled.
     *
     * @var integer|bool
     */
    public static $availableCustomerId = false;

    /**
     * PaymentMethodRepository constructor.
     * @param CreditCardRepository $creditCardRepository
     * @param PaypalBillingAgreementRepository $paypalBillingAgreementRepository
     */
    public function __construct(CreditCardRepository $creditCardRepository,
                                PaypalBillingAgreementRepository $paypalBillingAgreementRepository,
                                CustomerPaymentMethodsRepository $customerPaymentMethodsRepository,
                                UserPaymentMethodsRepository $userPaymentMethodsRepository)
    {
        parent::__construct();

        $this->creditCardRepository = $creditCardRepository;
        $this->paypalBillingAgreementRepository = $paypalBillingAgreementRepository;
        $this->customerPaymentMethodsRepository = $customerPaymentMethodsRepository;
        $this->userPaymentMethodsRepository = $userPaymentMethodsRepository;
    }

    /**
     * @return Builder
     */
    protected function query()
    {
        return (new PaymentMethodQueryBuilder(
            $this->connection(),
            $this->connection()->getQueryGrammar(),
            $this->connection()->getPostProcessor()
        ))
            ->from(ConfigService::$tablePaymentMethod);
    }

    /** Get payment method by id
     * @param int $id
     * @return array|mixed|null
     */
    public function getById($id)
    {
        $paymentMethod = $this->query()
            ->joinUserAndCustomerTables()
            ->selectColumns()
            ->restrictCustomerIdAccess()
            ->restrictUserIdAccess()
            ->where([ConfigService::$tablePaymentMethod . '.id' => $id])
            ->get()
            ->first();

        if (empty($paymentMethod)) {
            return null;
        }

        if ($paymentMethod['method_type'] == PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE) {
            $paymentMethod['method'] = $this->creditCardRepository->getById($paymentMethod['method_id']);
        } else if ($paymentMethod['method_type'] == PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE) {
            $paymentMethod['method'] = $this->paypalBillingAgreementRepository->getById($paymentMethod['method_id']);
        }

        unset($paymentMethod['method_id']);
        return $paymentMethod;
    }
}