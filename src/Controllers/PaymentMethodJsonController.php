<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\CreditCard;
use Railroad\Ecommerce\Entities\Customer;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Structures\Purchaser;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Entities\UserPaymentMethods;
use Railroad\Ecommerce\Events\PaymentMethods\PaymentMethodDeleted;
use Railroad\Ecommerce\Events\PaymentMethods\PaymentMethodUpdated;
use Railroad\Ecommerce\Events\PaypalPaymentMethodEvent;
use Railroad\Ecommerce\Events\UserDefaultPaymentMethodEvent;
use Railroad\Ecommerce\Exceptions\NotAllowedException;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Exceptions\PaymentMethodException;
use Railroad\Ecommerce\Exceptions\StripeCardException;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\AddressRepository;
use Railroad\Ecommerce\Repositories\CreditCardRepository;
use Railroad\Ecommerce\Repositories\CustomerRepository;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository;
use Railroad\Ecommerce\Requests\PaymentMethodCreatePaypalRequest;
use Railroad\Ecommerce\Requests\PaymentMethodCreateRequest;
use Railroad\Ecommerce\Requests\PaymentMethodSetDefaultRequest;
use Railroad\Ecommerce\Requests\PaymentMethodUpdateRequest;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\PaymentService;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Services\PermissionService;
use Spatie\Fractal\Fractal;
use Stripe\Error\Card;
use Throwable;

class PaymentMethodJsonController extends Controller
{
    /**
     * @var AddressRepository
     */
    private $addressRepository;

    /**
     * @var CreditCardRepository
     */
    private $creditCardRepository;

    /**
     * @var CurrencyService
     */
    private $currencyService;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var PaymentMethodService
     */
    private $paymentMethodService;

    /**
     * @var PaymentMethodRepository
     */
    private $paymentMethodRepository;

    /**
     * @var PaymentService
     */
    private $paymentService;

    /**
     * @var PayPalPaymentGateway
     */
    private $payPalPaymentGateway;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var StripePaymentGateway
     */
    private $stripePaymentGateway;

    /**
     * @var UserPaymentMethodsRepository
     */
    private $userPaymentMethodsRepository;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * PaymentMethodJsonController constructor.
     *
     * @param AddressRepository $addressRepository
     * @param CreditCardRepository $creditCardRepository
     * @param CurrencyService $currencyService
     * @param CustomerRepository $customerRepository
     * @param EcommerceEntityManager $entityManager
     * @param PaymentMethodService $paymentMethodService
     * @param PaymentMethodRepository $paymentMethodRepository
     * @param PaymentService $paymentService
     * @param PayPalPaymentGateway $payPalPaymentGateway
     * @param PermissionService $permissionService
     * @param StripePaymentGateway $stripePaymentGateway
     * @param UserPaymentMethodsRepository $userPaymentMethodsRepository
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        AddressRepository $addressRepository,
        CreditCardRepository $creditCardRepository,
        CurrencyService $currencyService,
        CustomerRepository $customerRepository,
        EcommerceEntityManager $entityManager,
        PaymentMethodService $paymentMethodService,
        PaymentMethodRepository $paymentMethodRepository,
        PaymentService $paymentService,
        PayPalPaymentGateway $payPalPaymentGateway,
        PermissionService $permissionService,
        StripePaymentGateway $stripePaymentGateway,
        UserPaymentMethodsRepository $userPaymentMethodsRepository,
        UserProviderInterface $userProvider
    )
    {
        $this->addressRepository = $addressRepository;
        $this->creditCardRepository = $creditCardRepository;
        $this->currencyService = $currencyService;
        $this->customerRepository = $customerRepository;
        $this->entityManager = $entityManager;
        $this->paymentMethodService = $paymentMethodService;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->paymentService = $paymentService;
        $this->payPalPaymentGateway = $payPalPaymentGateway;
        $this->permissionService = $permissionService;
        $this->stripePaymentGateway = $stripePaymentGateway;
        $this->userPaymentMethodsRepository = $userPaymentMethodsRepository;
        $this->userProvider = $userProvider;
    }

    /**
     * Get customer payment methods with all the method details: credit card or paypal billing agreement
     *
     * @param Request $request
     * @param $customerId
     *
     * @return Fractal
     *
     * @throws Throwable
     * @throws \Railroad\Permissions\Exceptions\NotAllowedException
     */
    public function getCustomerPaymentMethods(Request $request, $customerId)
    {
        /**
         * @var $customer Customer
         */
        $customer = $this->customerRepository->findOneBy(['id' => $customerId]);

        $this->permissionService->canOrThrow(auth()->id(), 'pull.customer.payment.method');

        throw_if(
            is_null($customer),
            new NotFoundException(
                'Pull failed, customer not found with id: ' . $customerId
            )
        );

        $paymentMethods = $this->paymentMethodRepository->getCustomerPaymentMethods($customer, $request);

        return ResponseService::paymentMethod(
            $paymentMethods
        );
    }

    /**
     * Get all user's payment methods with all the method details: credit card or paypal billing agreement
     *
     * @param int $userId
     * @param Request $request
     *
     * @return Fractal
     *
     * @throws Throwable
     */
    public function getUserPaymentMethods(Request $request, $userId)
    {
        /**
         * @var $user User
         */
        $user = $this->userProvider->getUserById($userId);

        if (empty($user) || $userId != auth()->id()) {
            $this->permissionService->canOrThrow(auth()->id(), 'pull.user.payment.method');
        }

        throw_if(
            is_null($user),
            new NotFoundException(
                'Pull failed, user not found with id: ' . $userId
            )
        );

        $paymentMethods = $this->paymentMethodRepository->getAllUsersPaymentMethods($user->getId(), $request);

        return ResponseService::paymentMethod(
            $paymentMethods
        );
    }

    /**
     * Call the service method to create a new payment method based on request parameters.
     * Return - NotFoundException if the request method type parameter it's not defined (paypal or credit card)
     *        - JsonResponse with the new created payment method
     *
     * @param PaymentMethodCreateRequest $request
     *
     * @return Fractal|NotFoundException
     *
     * @throws Throwable
     */
    public function store(PaymentMethodCreateRequest $request)
    {
        $userId = auth()->id();

        if ($this->permissionService->can(auth()->id(), 'create.payment.method')) {
            $userId = $request->get('user_id');
        }

        /**
         * @var $user User
         */
        $user = $this->userProvider->getUserById($userId);

        $purchaser = new Purchaser();

        $purchaser->setId($user->getId());
        $purchaser->setEmail($user->getEmail());
        $purchaser->setType(Purchaser::USER_TYPE);
        $purchaser->setBrand($request->get('gateway', config('ecommerce.brand')));

        try {
            if ($request->get('method_type') == PaymentMethod::TYPE_CREDIT_CARD) {

                // stripe customer is looked-up in db & reused or created
                $customer = $this->paymentService->getStripeCustomer($purchaser, $request->get('gateway'));

                $card = $this->stripePaymentGateway->createCustomerCard(
                    $request->get('gateway'),
                    $customer,
                    $request->get('card_token')
                );

                if (!empty($request->get('address_id'))) {
                    $address = $this->addressRepository->find($request->get('address_id'));
                }

                if (!empty($address)) {
                    $billingAddress = $address;
                } else {
                    // because the billing address is populated with data from the stripe card object
                    // this block can not be replaced with a call to $this->paymentService->createCreditCardPaymentMethod
                    // without modifying paymentService
                    $billingCountry = $card->address_country ?? $card->country;

                    // save billing address
                    $billingAddress = new Address();

                    $billingAddress->setType(Address::BILLING_ADDRESS_TYPE);
                    $billingAddress->setBrand($request->get('gateway', config('ecommerce.brand')));
                    $billingAddress->setUser($user);
                    $billingAddress->setRegion($card->address_state ?? '');
                    $billingAddress->setCountry($billingCountry ?? '');

                    $this->entityManager->persist($billingAddress);
                }

                $paymentMethod = $this->paymentMethodService->createCreditCardPaymentMethod(
                    $purchaser,
                    $billingAddress,
                    $card,
                    $customer,
                    $request->get('gateway'),
                    $request->get('currency', $this->currencyService->get()),
                    $request->get('set_default', false)
                );
            }
            else {
                throw new NotAllowedException('Payment method not supported.');
            }
        } catch (Card $exception) {
            $exceptionData = $exception->getJsonBody();

            // validate UI known error format
            if (isset($exceptionData['error']) && isset($exceptionData['error']['code'])) {
                throw new StripeCardException(var_export($exceptionData['error'], true));
            }

            // throw generic
            throw new PaymentFailedException($exception->getMessage());
        } catch (Exception $paymentFailedException) {
            throw new PaymentFailedException($paymentFailedException->getMessage());
        }

        return ResponseService::paymentMethod($paymentMethod);
    }

    /**
     * @return JsonResponse
     * @throws PaymentFailedException
     */
    public function getPaypalUrl()
    {
        $url = $this->payPalPaymentGateway->getBillingAgreementExpressCheckoutUrl(
            config('ecommerce.brand'),
            url()->route(config('ecommerce.paypal.agreement_route'))
        );

        return response()->json(['url' => $url]);
    }

    /**
     * @param PaymentMethodCreatePaypalRequest $request
     *
     * @return JsonResponse|RedirectResponse
     *
     * @throws Throwable
     */
    public function paypalAgreement(PaymentMethodCreatePaypalRequest $request)
    {
        if ($request->has('token')) {

            $billingAgreementId = $this->payPalPaymentGateway->createBillingAgreement(
                config('ecommerce.brand'),
                '',
                '',
                $request->get('token')
            );

            /** @var $user User */
            $user = $this->userProvider->getCurrentUser();

            $purchaser = new Purchaser();

            $brand = $request->get('gateway', config('ecommerce.brand'));

            $purchaser->setId($user->getId());
            $purchaser->setEmail($user->getEmail());
            $purchaser->setType(Purchaser::USER_TYPE);
            $purchaser->setBrand($brand);

            $billingAddress = new Address();

            $billingAddress->setType(Address::BILLING_ADDRESS_TYPE);
            $billingAddress->setBrand($brand);
            $billingAddress->setUser($user);
            $billingAddress->setRegion('');
            $billingAddress->setCountry('');

            $this->entityManager->persist($billingAddress);

            $paymentMethod = $this->paymentMethodService->createPayPalPaymentMethod(
                $purchaser,
                $billingAddress,
                $billingAgreementId,
                config('ecommerce.brand'),
                $this->currencyService->get(),
                false
            );

            event(new PaypalPaymentMethodEvent($paymentMethod->getId()));
        }

        if (config('ecommerce.paypal.agreement_fulfilled_path')) {
            return redirect()->to(config('ecommerce.paypal.agreement_fulfilled_path'));
        }

        return ResponseService::empty(204);
    }

    /**
     * @param PaymentMethodSetDefaultRequest $request
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function setDefault(PaymentMethodSetDefaultRequest $request)
    {
        $userPaymentMethod = $this->userPaymentMethodsRepository->getByMethodId($request->get('id'));

        /**
         * @var $paymentMethod PaymentMethod
         */
        $paymentMethod = $userPaymentMethod->getPaymentMethod();
        $oldPaymentMethod = clone $paymentMethod;

        /**
         * @var $user User
         */
        $user = $userPaymentMethod->getUser();

        if ($user && ($user->getId() ?? 0) !== auth()->id()) {
            $this->permissionService->canOrThrow(
                auth()->id(),
                'update.payment.method'
            );
        }

        $old = $this->userPaymentMethodsRepository->getUserPrimaryPaymentMethod($user);

        if ($old) {
            $old->setIsPrimary(false);
        }

        $userPaymentMethod->setIsPrimary(true);

        $this->entityManager->flush();

        event(new PaymentMethodUpdated($paymentMethod, $oldPaymentMethod, $user));

        event(
            new UserDefaultPaymentMethodEvent(
                $user->getId(), $paymentMethod->getId()
            )
        );

        return ResponseService::empty(204);
    }

    /**
     * Update a payment method based on request data and payment method id.
     * Return - NotFoundException if the payment method doesn't exist or the user have not rights to access it
     *        - JsonResponse with the updated payment method
     *
     * @param PaymentMethodUpdateRequest $request
     * @param int $paymentMethodId
     *
     * @return Fractal
     *
     * @throws Throwable
     */
    public function update(PaymentMethodUpdateRequest $request, $paymentMethodId)
    {
        $paymentMethod = $this->paymentMethodRepository->byId($paymentMethodId);

        $message = 'Update failed, payment method not found with id: ' . $paymentMethodId;

        throw_if(is_null($paymentMethod), new NotFoundException($message));

        $message = 'Only credit card payment methods may be updated';

        throw_if(
            (PaymentMethod::TYPE_CREDIT_CARD != $paymentMethod->getMethodType()),
            new PaymentMethodException($message)
        );

        $userPaymentMethod = $this->userPaymentMethodsRepository->getByMethodId($paymentMethodId);

        if ($userPaymentMethod &&
            $userPaymentMethod->getUser() &&
            ($userPaymentMethod->getUser()
                    ->getId() ?? 0) !== auth()->id()) {
            $this->permissionService->canOrThrow(auth()->id(), 'update.payment.method');
        }

        if ($request->get('set_default') &&
            $primary =
                $this->userPaymentMethodsRepository->getUserPrimaryPaymentMethod($userPaymentMethod->getUser())) {
            /**
             * @var $primary UserPaymentMethods
             */
            $primary->setIsPrimary(false);

            $userPaymentMethod->setIsPrimary(true);
        }

        try {
            /**
             * @var $method CreditCard
             */
            $method = $this->creditCardRepository->find(
                $paymentMethod->getMethod()
                    ->getId()
            );
            $oldPaymentMethod = clone($paymentMethod);

            // fetch the existing customer data from db and do not create an other customer record
            $customer = $this->stripePaymentGateway->getCustomer(
                $request->get('gateway'),
                $method->getExternalCustomerId()
            );

            $card = $this->stripePaymentGateway->getCard(
                $customer,
                $method->getExternalId(),
                $request->get('gateway')
            );

            $card = $this->stripePaymentGateway->updateCard(
                $request->get('gateway'),
                $card,
                $request->get('month'),
                $request->get('year'),
                $request->get('country', $request->has('country') ? '' : null),
                $request->get('region', $request->has('region') ? '' : null)
            );

            $expirationDate = Carbon::createFromDate(
                $request->get('year'),
                $request->get('month')
            );

            // update existing card data
            $method->setFingerprint($card->fingerprint);
            $method->setLastFourDigits($card->last4);
            $method->setCardholderName($card->name);
            $method->setCompanyName($card->brand);
            $method->setExpirationDate($expirationDate->startOfMonth());
            $method->setExternalId($card->id);
            $method->setExternalCustomerId($card->customer);
            $method->setPaymentGatewayName($request->get('gateway'));
            $method->setUpdatedAt(Carbon::now());

            $billingCountry = $card->address_country ?? $card->country;

            $address = $this->addressRepository->byId($paymentMethod->getBillingAddress());

            $address->setRegion($card->address_state ?? '');
            $address->setCountry($billingCountry ?? '');
            $address->setUpdatedAt(Carbon::now());

            $this->entityManager->flush();

            event(new PaymentMethodUpdated($paymentMethod, $oldPaymentMethod, $userPaymentMethod->getUser()));

            event(
                new UserDefaultPaymentMethodEvent(
                    $userPaymentMethod->getUser()
                        ->getId(), $paymentMethod->getId()
                )
            );

        } catch (Card $exception) {

            $exceptionData = $exception->getJsonBody();

            // validate UI known error format
            if (isset($exceptionData['error']) && isset($exceptionData['error']['code'])) {
                throw new StripeCardException($exceptionData['error']);
            }

            // throw generic
            throw new PaymentFailedException($exception->getMessage());

        } catch (Exception $paymentFailedException) {
            throw new PaymentFailedException($paymentFailedException->getMessage());
        }

        return ResponseService::paymentMethod($paymentMethod);
    }

    /**
     * Delete a payment method and return a JsonResponse.
     *
     * @param int $paymentMethodId
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function delete($paymentMethodId)
    {
        $paymentMethod = $this->paymentMethodRepository->byId($paymentMethodId);

        throw_if(
            is_null($paymentMethod),
            new NotFoundException(
                'Delete failed, payment method not found with id: ' . $paymentMethodId
            )
        );

        $userPaymentMethod = $this->userPaymentMethodsRepository->getByMethodId($paymentMethodId);

        if (empty($userPaymentMethod) ||
            ($userPaymentMethod->getUser() &&
                $userPaymentMethod->getUser()
                    ->getId() !== auth()->id())) {

            $this->permissionService->canOrThrow(
                auth()->id(),
                'delete.payment.method'
            );
        }

        if (!empty($userPaymentMethod)) {
            throw_if(
                $userPaymentMethod->getIsPrimary(),
                new NotAllowedException(
                    'Delete failed, can not delete the default payment method'
                )
            );
        }

        throw_if(
            !empty($paymentMethod->getDeletedAt()),
            new NotAllowedException(
                'Payment method already soft-deleted'
            )
        );

        $this->entityManager->remove($paymentMethod);
        $this->entityManager->flush();

        event(new PaymentMethodDeleted($paymentMethod));

        return ResponseService::empty(204);
    }
}
