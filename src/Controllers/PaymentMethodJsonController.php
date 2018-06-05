<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotAllowedException;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Repositories\AddressRepository;
use Railroad\Ecommerce\Repositories\CreditCardRepository;
use Railroad\Ecommerce\Repositories\CustomerPaymentMethodsRepository;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\PaypalBillingAgreementRepository;
use Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository;
use Railroad\Ecommerce\Requests\PaymentMethodCreateRequest;
use Railroad\Ecommerce\Requests\PaymentMethodDeleteRequest;
use Railroad\Ecommerce\Requests\PaymentMethodUpdateRequest;
use Railroad\Ecommerce\Responses\JsonResponse;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Permissions\Services\PermissionService;

class PaymentMethodJsonController extends Controller
{
    /**
     * @var \Railroad\Permissions\Services\PermissionService
     */
    private $permissionService;

    /**
     * @var PaymentMethodRepository
     */
    private $paymentMethodRepository;

    /**
     * @var UserPaymentMethodsRepository
     */
    private $userPaymentMethodRepository;

    /**
     * @var CustomerPaymentMethodsRepository
     */
    private $customerPaymentMethodRepository;

    /**
     * @var \Railroad\Ecommerce\Services\PaymentMethodService
     */
    private $paymentMethodService;

    /**
     * @var \Railroad\Ecommerce\Gateways\StripePaymentGateway
     */
    private $stripePaymentGateway;

    /**
     * @var \Railroad\Ecommerce\Gateways\PayPalPaymentGateway
     */
    private $payPalPaymentGateway;

    /**
     * @var \Railroad\Ecommerce\Services\CurrencyService
     */
    private $currencyService;

    /**
     * @var \Railroad\Ecommerce\Repositories\AddressRepository
     */
    private $addressRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\CreditCardRepository
     */
    private $creditCardRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\PaypalBillingAgreementRepository
     */
    private $paypalBillingAgreementRepository;

    /**
     * PaymentMethodJsonController constructor.
     *
     * @param \Railroad\Permissions\Services\PermissionService                  $permissionService
     * @param \Railroad\Ecommerce\Repositories\PaymentMethodRepository          $paymentMethodRepository
     * @param \Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository     $userPaymentMethodsRepository
     * @param \Railroad\Ecommerce\Repositories\CustomerPaymentMethodsRepository $customerPaymentMethodsRepository
     */
    public function __construct(
        PermissionService $permissionService,
        PaymentMethodRepository $paymentMethodRepository,
        UserPaymentMethodsRepository $userPaymentMethodsRepository,
        CustomerPaymentMethodsRepository $customerPaymentMethodsRepository,
        PaymentMethodService $paymentMethodService,
        StripePaymentGateway $stripePaymentGateway,
        PayPalPaymentGateway $payPalPaymentGateway,
        CurrencyService $currencyService,
        AddressRepository $addressRepository,
        CreditCardRepository $creditCardRepository,
        PaypalBillingAgreementRepository $paypalBillingAgreementRepository
    ) {
        $this->permissionService                = $permissionService;
        $this->paymentMethodRepository          = $paymentMethodRepository;
        $this->userPaymentMethodRepository      = $userPaymentMethodsRepository;
        $this->customerPaymentMethodRepository  = $customerPaymentMethodsRepository;
        $this->paymentMethodService             = $paymentMethodService;
        $this->stripePaymentGateway             = $stripePaymentGateway;
        $this->payPalPaymentGateway             = $payPalPaymentGateway;
        $this->currencyService                  = $currencyService;
        $this->addressRepository                = $addressRepository;
        $this->creditCardRepository             = $creditCardRepository;
        $this->paypalBillingAgreementRepository = $paypalBillingAgreementRepository;

        $this->middleware(ConfigService::$middleware);
    }

    /** Call the service method to create a new payment method based on request parameters.
     * Return - NotFoundException if the request method type parameter it's not defined (paypal or credit card)
     *        - JsonResponse with the new created payment method
     *
     * @param PaymentMethodCreateRequest $request
     * @return JsonResponse|NotFoundException
     */
    public function store(PaymentMethodCreateRequest $request)
    {
        $user = auth()->user();
        try
        {
            if($request->get('method_type') == PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE)
            {
                $customer = $this->stripePaymentGateway->getOrCreateCustomer(
                    $request->get('payment_gateway'),
                    $user['email']
                );

                $cardToken = $this->stripePaymentGateway->createCardToken(
                    $request->get('payment_gateway'),
                    $request->get('card_fingerprint'),
                    $request->get('card_month'),
                    $request->get('card_year'),
                    $request->get('card_number_last_four_digits'),
                    $request->get('cardholder_name')

                );

                $card = $this->stripePaymentGateway->createCustomerCard(
                    $request->get('payment_gateway'), $customer, $cardToken->id
                );

                $paymentMethodId = $this->paymentMethodService->createUserCreditCard(
                    $user['id'],
                    $card->fingerprint,
                    $card->last4,
                    $request->get('cardholder_name'),
                    $card->brand,
                    $card->exp_year,
                    $card->exp_month,
                    $card->id,
                    $card->customer,
                    $request->get('payment_gateway'),
                    null,
                    $request->get('currency', $this->currencyService->get()),
                    true);
            }
            else if($request->get('method_type') == PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE)
            {
                $billingAgreementId =
                    $this->payPalPaymentGateway->createBillingAgreement(
                        $request->get('payment_gateway'),
                        '',
                        '',
                        $request->get('validated-express-checkout-token')
                    );

                // save billing address
                $billingAddressDB = $this->addressRepository->create(
                    [
                        'type'       => CartAddressService::BILLING_ADDRESS_TYPE,
                        'brand'      => ConfigService::$brand,
                        'user_id'    => $user['id'],
                        'state'      => $request->get('billing_region'),
                        'country'    => $request->get('billing_country'),
                        'created_on' => Carbon::now()->toDateTimeString(),
                    ]
                );

                $paymentMethodId = $this->paymentMethodService->createPayPalBillingAgreement(
                    $user['id'],
                    $billingAgreementId,
                    $billingAddressDB['id'],
                    $request->get('payment_gateway'),
                    $request->get('currency', $this->currencyService->get()),
                    true
                );
            }
            else
            {
                throw new NotAllowedException('Payment method not supported.');
            }
        }
        catch(\Exception $paymentFailedException)
        {
            throw new PaymentFailedException($paymentFailedException->getMessage());
        }

        $paymentMethod = $this->paymentMethodRepository->read($paymentMethodId);

        return new JsonResponse($paymentMethod, 200);
    }

    /** Update a payment method based on request data and payment method id.
     * Return - NotFoundException if the payment method doesn't exist or the user have not rights to access it
     *        - JsonResponse with the updated payment method
     *
     * @param PaymentMethodUpdateRequest $request
     * @param integer                    $paymentMethodId
     * @return JsonResponse|NotFoundException
     */
    public function update(PaymentMethodUpdateRequest $request, $paymentMethodId)
    {
        $user          = auth()->user();
        $paymentMethod = $this->paymentMethodRepository->read($paymentMethodId);

        if($paymentMethod['user_id'] !== auth()->id())
        {
            $this->permissionService->canOrThrow(auth()->id(), 'update.payment.method');
        }

        //if the payment method not exist; we throw the proper exception
        throw_if(
            is_null($paymentMethod),
            new NotFoundException('Update failed, payment method not found with id: ' . $paymentMethodId)
        );

        switch($request->get('update_method'))
        {
            case 'create-credit-card':
                $methodType = PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE;
                $create     = true;
                $customer   = $this->stripePaymentGateway->getOrCreateCustomer(
                    $request->get('payment_gateway'),
                    $user['email']
                );

                $cardToken = $this->stripePaymentGateway->createCardToken(
                    $request->get('payment_gateway'),
                    $request->get('card_fingerprint'),
                    $request->get('card_month'),
                    $request->get('card_year'),
                    $request->get('card_number_last_four_digits'),
                    $request->get('cardholder_name')

                );

                $card   = $this->stripePaymentGateway->createCustomerCard(
                    $request->get('payment_gateway'), $customer, $cardToken->id
                );
                $method = $this->creditCardRepository->create(
                    [
                        'fingerprint'          => $card->fingerprint,
                        'last_four_digits'     => $card->last4,
                        'cardholder_name'      => $request->get('cardholder_name'),
                        'company_name'         => $card->brand,
                        'expiration_date'      => Carbon::createFromDate($card->exp_year, $card->exp_month)->toDateTimeString(),
                        'external_id'          => $card->id,
                        'external_customer_id' => $card->customer,
                        'payment_gateway_name' => $request->get('payment_gateway'),
                        'created_on'           => Carbon::now()->toDateTimeString(),
                    ]
                );

                break;
            case 'use-paypal':
                $methodType         = PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE;
                $create             = true;
                $billingAgreementId =
                    $this->payPalPaymentGateway->createBillingAgreement(
                        $request->get('payment_gateway'),
                        '',
                        '',
                        $request->get('validated-express-checkout-token')
                    );

                // save billing address
                $billingAddressDB = $this->addressRepository->create(
                    [
                        'type'       => CartAddressService::BILLING_ADDRESS_TYPE,
                        'brand'      => ConfigService::$brand,
                        'user_id'    => $user['id'],
                        'state'      => $request->get('billing_region'),
                        'country'    => $request->get('billing_country'),
                        'created_on' => Carbon::now()->toDateTimeString(),
                    ]
                );
                $method           = $this->paypalBillingAgreementRepository->create(
                    [
                        'external_id'          => $billingAgreementId,
                        'payment_gateway_name' => $request->get('payment_gateway'),
                        'created_on'           => Carbon::now()->toDateTimeString(),
                    ]
                );

                break;
            default:
                $this->creditCardRepository->update(
                    $paymentMethod['method']['id'],
                    [
                        Carbon::create(
                            $request->get('card_year'),
                            $request->get('card_month'),
                            12,
                            0,
                            0,
                            0
                        ),
                        'updated_on' => Carbon::now()->toDateTimeString(),
                    ]
                );
        }

        //update payment method
        $paymentMethodUpdated = $this->paymentMethodRepository->update(
            $paymentMethodId,
            array_merge(
                $request->only(
                    [
                        'currency',
                    ]
                ),
                [
                    'method_type' => $methodType,
                    'method_id'   => $method['id'] ?? $paymentMethod['id'],
                    'updated_on'  => Carbon::now()->toDateTimeString(),
                ]
            )
        );

        return new JsonResponse($paymentMethodUpdated, 201);
    }

    /**
     * Delete a payment method and return a JsonResponse.
     *  Throw  - NotFoundException if the payment method not exist
     *
     * @param PaymentMethodDeleteRequest $request
     * @param integer                    $paymentMethodId
     * @return JsonResponse
     */
    public function delete(PaymentMethodDeleteRequest $request, $paymentMethodId)
    {
        $paymentMethod = $this->paymentMethodRepository->read($paymentMethodId);
        if($paymentMethod['user_id'] !== auth()->id())
        {
            $this->permissionService->canOrThrow(auth()->id(), 'delete.payment.method');
        }

        throw_if(
            is_null($paymentMethod),
            new NotFoundException('Delete failed, payment method not found with id: ' . $paymentMethodId)
        );

        $results = $this->paymentMethodRepository->destroy($paymentMethodId);

        return new JsonResponse(null, 204);
    }

    /**
     * @param \Railroad\Ecommerce\Requests\PaymentMethodCreateRequest $request
     * @param                                                         $paymentMethod
     */
    private function assignPaymentMethod($request, $paymentMethod)
    {
        if($request->filled('user_id'))
        {
            $this->userPaymentMethodRepository->create(
                [
                    'user_id'           => $request->get('user_id'),
                    'payment_method_id' => $paymentMethod['id'],
                    'created_on'        => Carbon::now()->toDateTimeString(),
                ]
            );
        }

        if($request->filled('customer_id'))
        {
            $this->customerPaymentMethodRepository->create(
                [
                    'customer_id'       => $request->get('customer_id'),
                    'payment_method_id' => $paymentMethod['id'],
                    'created_on'        => Carbon::now()->toDateTimeString(),
                ]
            );
        }
    }

    /**
     * @param  $paymentMethod
     */
    private function revokePaymentMethod($paymentMethod)
    {
        $this->userPaymentMethodRepository->query()->where(
            [
                'payment_method_id' => $paymentMethod['id'],
            ]
        )->delete();

        $this->customerPaymentMethodRepository->query()->where(
            [
                'payment_method_id' => $paymentMethod['id'],
            ]
        )->delete();
    }
}