<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\Address;
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
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\PaypalBillingAgreementRepository;
use Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository;
use Railroad\Ecommerce\Requests\PaymentMethodCreateRequest;
use Railroad\Ecommerce\Requests\PaymentMethodUpdateRequest;
use Railroad\Ecommerce\Requests\PaymentMethodCreatePaypalRequest;
use Railroad\Ecommerce\Requests\PaymentMethodSetDefaultRequest;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\JsonApiHydrator;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Services\PermissionService;
use Spatie\Fractal\Fractal;
use Stripe\Error\Card;
use Exception;
use Throwable;

class PaymentMethodJsonController extends BaseController
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
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var JsonApiHydrator
     */
    private $jsonApiHydrator;

    /**
     * @var PaymentMethodService
     */
    private $paymentMethodService;

    /**
     * @var PaymentMethodRepository
     */
    private $paymentMethodRepository;

    /**
     * @var PaypalBillingAgreementRepository
     */
    private $paypalBillingAgreementRepository;

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
     * @param AddressRepository $addressRepository,
     * @param CreditCardRepository $creditCardRepository,
     * @param CurrencyService $currencyService,
     * @param EcommerceEntityManager $entityManager,
     * @param JsonApiHydrator $jsonApiHydrator,
     * @param PaymentMethodService $paymentMethodService,
     * @param PaymentMethodRepository $paymentMethodRepository,
     * @param PaypalBillingAgreementRepository $paypalBillingAgreementRepository,
     * @param PayPalPaymentGateway $payPalPaymentGateway,
     * @param PermissionService $permissionService,
     * @param StripePaymentGateway $stripePaymentGateway,
     * @param UserPaymentMethodsRepository $userPaymentMethodsRepository,
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        AddressRepository $addressRepository,
        CreditCardRepository $creditCardRepository,
        CurrencyService $currencyService,
        EcommerceEntityManager $entityManager,
        JsonApiHydrator $jsonApiHydrator,
        PaymentMethodService $paymentMethodService,
        PaymentMethodRepository $paymentMethodRepository,
        PaypalBillingAgreementRepository $paypalBillingAgreementRepository,
        PayPalPaymentGateway $payPalPaymentGateway,
        PermissionService $permissionService,
        StripePaymentGateway $stripePaymentGateway,
        UserPaymentMethodsRepository $userPaymentMethodsRepository,
        UserProviderInterface $userProvider
    ) {
        parent::__construct();

        $this->addressRepository = $addressRepository;
        $this->creditCardRepository = $creditCardRepository;
        $this->currencyService = $currencyService;
        $this->entityManager = $entityManager;
        $this->jsonApiHydrator = $jsonApiHydrator;
        $this->paymentMethodService = $paymentMethodService;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->paypalBillingAgreementRepository = $paypalBillingAgreementRepository;
        $this->payPalPaymentGateway = $payPalPaymentGateway;
        $this->permissionService = $permissionService;
        $this->stripePaymentGateway = $stripePaymentGateway;
        $this->userPaymentMethodsRepository = $userPaymentMethodsRepository;
        $this->userProvider = $userProvider;
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
         * @var $user \Railroad\Ecommerce\Entities\User
         */
        $user = $this->userProvider->getUserById($userId);

        // may be refactored in user provider, then reused in OrderFormSubmitRequest::getPurchaser
        $purchaser = new Purchaser();

        $purchaser->setId($user->getId());
        $purchaser->setEmail($user->getEmail());
        $purchaser->setType(Purchaser::USER_TYPE);
        $purchaser->setBrand($this->get('gateway', ConfigService::$brand));

        try {
            if (
                $request->get('method_type') ==
                PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE
            ) {
                // todo - review stripe customer and card retrival (& PaymentService chargeNewCreditCartPaymentMethod possible reuse)
                $customer = $this->stripePaymentGateway->getOrCreateCustomer(
                    $request->get('gateway'),
                    $user->getEmail()
                );

                $card = $this->stripePaymentGateway->createCustomerCard(
                    $request->get('gateway'),
                    $customer,
                    $request->get('card_token')
                );

                // todo - review & update billingAddress creation, if necessary
                $billingCountry = $card->address_country ?? $card->country;

                // save billing address
                $billingAddress = new Address();

                $billingAddress
                    ->setType(CartAddressService::BILLING_ADDRESS_TYPE)
                    ->setBrand(ConfigService::$brand)
                    ->setUser($user)
                    ->setState($card->address_state ?? '')
                    ->setCountry($billingCountry ?? '');

                $this->entityManager->persist($billingAddress);

                $paymentMethod = $this->paymentMethodService->createCreditCardPaymentMethod(
                    $purchaser,
                    $billingAddress,
                    $card,
                    $customer,
                    $request->get('gateway'),
                    $request->get('currency', $this->currencyService->get()),
                    $request->get('set_default', false)
                );

            } else {
                throw new NotAllowedException('Payment method not supported.');
            }
        } catch (Card $exception) {
            $exceptionData = $exception->getJsonBody();

            // validate UI known error format
            if (isset($exceptionData['error']) && isset($exceptionData['error']['code'])) {
                throw new StripeCardException($exceptionData['error']);
            }

            // throw generic
            throw new PaymentFailedException($exception->getMessage());
        } catch(\Exception $paymentFailedException) {
            throw new PaymentFailedException($paymentFailedException->getMessage());
        }

        return ResponseService::paymentMethod($paymentMethod);
    }

    /**
     * @throws \Railroad\Ecommerce\Exceptions\PaymentFailedException
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaypalUrl()
    {
        $url = $this->payPalPaymentGateway
            ->getBillingAgreementExpressCheckoutUrl(
                ConfigService::$brand,
                url()->route(ConfigService::$paypalAgreementRoute)
            );

        return response()->json(['url' => $url]);
    }

    /**
     * @param PaymentMethodCreatePaypalRequest $request
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function paypalAgreement(PaymentMethodCreatePaypalRequest $request)
    {
        if ($request->has('token')) {

            $billingAgreementId =
                $this->payPalPaymentGateway->createBillingAgreement(
                    ConfigService::$brand,
                    '',
                    '',
                    $request->get('token')
                );

            /**
             * @var $user \Railroad\Ecommerce\Entities\User
             */
            $user = $this->userProvider->getCurrentUser();

            $purchaser = new Purchaser();

            $purchaser->setId($user->getId());
            $purchaser->setEmail($user->getEmail());
            $purchaser->setType(Purchaser::USER_TYPE);
            $purchaser->setBrand($this->get('gateway', ConfigService::$brand));

            $billingAddress = new Address();

            $billingAddress
                ->setType(CartAddressService::BILLING_ADDRESS_TYPE)
                ->setBrand(ConfigService::$brand)
                ->setUser($user)
                ->setState('')
                ->setCountry('');

            $this->entityManager->persist($billingAddress);

            $paymentMethod = $this->paymentMethodService->createPayPalPaymentMethod(
                $purchaser,
                $billingAddress,
                $billingAgreementId,
                ConfigService::$brand,
                $this->currencyService->get(),
                false
            );

            event(new PaypalPaymentMethodEvent($paymentMethod->getId()));
        }

        if (ConfigService::$paypalAgreementFulfilledRoute) {
            return redirect()->route(ConfigService::$paypalAgreementFulfilledRoute);
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
        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->userPaymentMethodsRepository->createQueryBuilder('p');

        $userPaymentMethod = $qb
            ->where($qb->expr()->eq('IDENTITY(p.paymentMethod)', ':id'))
            ->setParameter('id', $request->get('id'))
            ->getQuery()
            ->getOneOrNullResult();

        /**
         * @var $paymentMethod \Railroad\Ecommerce\Entities\PaymentMethod
         */
        $paymentMethod = $userPaymentMethod->getPaymentMethod();

        /**
         * @var $user \Railroad\Ecommerce\Entities\User
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

        event(
            new UserDefaultPaymentMethodEvent(
                $user->getId(),
                $paymentMethod->getId()
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
        $paymentMethod = $this->paymentMethodRepository
                                ->find($paymentMethodId);

        $message = 'Update failed, payment method not found with id: '
            . $paymentMethodId;

        throw_if(is_null($paymentMethod), new NotFoundException($message));

        $message = 'Only credit card payment methods may be updated';

        throw_if(
            (PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE != $paymentMethod->getMethodType()),
            new PaymentMethodException($message)
        );

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->userPaymentMethodsRepository->createQueryBuilder('upm');

        $userPaymentMethod = $qb
            ->select(['upm'])
            ->where($qb->expr()->eq('IDENTITY(upm.paymentMethod)', ':id'))
            ->setParameter('id', $paymentMethodId)
            ->getQuery()
            ->getOneOrNullResult();

        if (
            $userPaymentMethod &&
            $userPaymentMethod->getUser() &&
            ($userPaymentMethod->getUser()->getId() ?? 0) !== auth()->id()
        ) {
            $this->permissionService
                ->canOrThrow(auth()->id(), 'update.payment.method');
        }

        if (
            $request->get('set_default') &&
            $primary = $this->userPaymentMethodsRepository
                ->getUserPrimaryPaymentMethod($userPaymentMethod->getUser())
        ) {
            /**
             * @var $primary \Railroad\Ecommerce\Entities\UserPaymentMethods
             */
            $primary->setIsPrimary(false);

            $userPaymentMethod->setIsPrimary(true);
        }

        try {
            /**
             * @var $method \Railroad\Ecommerce\Entities\CreditCard
             */
            $method = $this->creditCardRepository
                            ->find($paymentMethod->getMethodId());

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
                $request->get('state', $request->has('state') ? '' : null)
            );

            $expirationDate = Carbon::createFromDate(
                $request->get('year'),
                $request->get('month')
            );

            $method
                ->setFingerprint($card->fingerprint)
                ->setLastFourDigits($card->last4)
                ->setCardholderName($card->name)
                ->setCompanyName($card->brand)
                ->setExpirationDate($expirationDate->startOfMonth())
                ->setExternalId($card->id)
                ->setExternalCustomerId($card->customer)
                ->setPaymentGatewayName($request->get('gateway'))
                ->setUpdatedAt(Carbon::now());

            $billingCountry = $card->address_country ?? $card->country;

            $address = $this->addressRepository
                                ->find($paymentMethod->getBillingAddress());

            $address
                ->setState($card->address_state ?? '')
                ->setCountry($billingCountry ?? '')
                ->setUpdatedAt(Carbon::now());

            $this->entityManager->flush();

            event(
                new UserDefaultPaymentMethodEvent(
                    $userPaymentMethod->getUser()->getId(),
                    $paymentMethod->getId()
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
        $paymentMethod = $this->paymentMethodRepository
                            ->find($paymentMethodId);

        throw_if(
            is_null($paymentMethod),
            new NotFoundException(
                'Delete failed, payment method not found with id: ' .
                $paymentMethodId
            )
        );

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->userPaymentMethodsRepository->createQueryBuilder('upm');

        $userPaymentMethod = $qb
            ->select(['upm'])
            ->where($qb->expr()->eq('IDENTITY(upm.paymentMethod)', ':id'))
            ->setParameter('id', $paymentMethodId)
            ->getQuery()
            ->getOneOrNullResult();

        if (
            $userPaymentMethod->getUser() &&
            $userPaymentMethod->getUser()->getId() !== auth()->id()
        ) {
            $this->permissionService->canOrThrow(
                auth()->id(),
                'delete.payment.method'
            );
        }

        throw_if(
            $userPaymentMethod->getIsPrimary(),
            new NotAllowedException(
                'Delete failed, can not delete the default payment method'
            )
        );

        $paymentMethod->setDeletedOn(Carbon::now());

        $this->entityManager->flush();

        return ResponseService::empty(204);
    }

    /**
     * Get all user's payment methods with all the method details: credit card or paypal billing agreement
     *
     * @param int $userId
     *
     * @return Fractal
     *
     * @throws Throwable
     */
    public function getUserPaymentMethods($userId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.user.payment.method');

        /**
         * @var $user \Railroad\Ecommerce\Entities\User
         */
        $user = $this->userProvider->getUserById($userId);

        throw_if(
            is_null($user),
            new NotFoundException(
                'Pull failed, user not found with id: ' .
                $userId
            )
        );

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->userPaymentMethodsRepository->createQueryBuilder('upm');

        $userPaymentMethods = $qb
            ->select(['upm', 'pm'])
            ->join('upm.paymentMethod', 'pm')
            ->where($qb->expr()->eq('upm.user', ':user'))
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $creditCardIds = [];
        $paypalIds = [];

        /**
         * @var $userPaymentMethod \Railroad\Ecommerce\Entities\UserPaymentMethods
         */
        foreach ($userPaymentMethods as $userPaymentMethod) {

            /**
             * @var $paymentMethod \Railroad\Ecommerce\Entities\PaymentMethod
             */
            $paymentMethod = $userPaymentMethod->getPaymentMethod();

            $type = $paymentMethod->getMethodType();

            if ($type == PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE) {
                $paypalIds[] = $paymentMethod->getMethodId();
            } else {
                $creditCardIds[] = $paymentMethod->getMethodId();
            }
        }

        $creditCardsMap = $this->creditCardRepository
                                    ->getCreditCardsMap($creditCardIds);

        $paypalAgreementsMap = $this->paypalBillingAgreementRepository
                                        ->getPaypalAgreementsMap($paypalIds);

        return ResponseService::userPaymentMethods(
            $userPaymentMethods,
            $creditCardsMap,
            $paypalAgreementsMap
        );
    }
}
