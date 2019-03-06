<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Illuminate\Http\Request;
use Railroad\DoctrineArrayHydrator\JsonApiHydrator;
use Railroad\Ecommerce\Contracts\UserInterface;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\CreditCard;
use Railroad\Ecommerce\Entities\PaypalBillingAgreement;
use Railroad\Ecommerce\Entities\UserPaymentMethods;
use Railroad\Ecommerce\Events\PaypalPaymentMethodEvent;
use Railroad\Ecommerce\Events\UserDefaultPaymentMethodEvent;
use Railroad\Ecommerce\Exceptions\NotAllowedException;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Exceptions\PaymentMethodException;
use Railroad\Ecommerce\Exceptions\StripeCardException;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Requests\PaymentMethodCreateRequest;
use Railroad\Ecommerce\Requests\PaymentMethodUpdateRequest;
use Railroad\Ecommerce\Requests\PaymentMethodCreatePaypalRequest;
use Railroad\Ecommerce\Requests\PaymentMethodSetDefaultRequest;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Exceptions\NotAllowedException as PermissionsNotAllowedException;
use Railroad\Permissions\Services\PermissionService;
use Stripe\Error\Card;
use Exception;

class PaymentMethodJsonController extends BaseController
{
    /**
     * @var \Railroad\Ecommerce\Services\CurrencyService
     */
    private $currencyService;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var JsonApiHydrator
     */
    private $jsonApiHydrator;

    /**
     * @var \Railroad\Ecommerce\Gateways\PayPalPaymentGateway
     */
    private $payPalPaymentGateway;

    /**
     * @var \Railroad\Permissions\Services\PermissionService
     */
    private $permissionService;

    /**
     * @var \Railroad\Ecommerce\Gateways\StripePaymentGateway
     */
    private $stripePaymentGateway;

    /**
     * @var EntityRepository
     */
    private $paymentMethodRepository;

    /**
     * @var EntityRepository
     */
    private $userPaymentMethodsRepository;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * PaymentMethodJsonController constructor.
     *
     * @param CurrencyService $currencyService
     * @param EntityManager $entityManager
     * @param JsonApiHydrator $jsonApiHydrator
     * @param StripePaymentGateway $stripePaymentGateway
     * @param PaymentMethodService $paymentMethodService
     * @param PayPalPaymentGateway $payPalPaymentGateway
     * @param PermissionService $permissionService
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        CurrencyService $currencyService,
        EntityManager $entityManager,
        JsonApiHydrator $jsonApiHydrator,
        StripePaymentGateway $stripePaymentGateway,
        PaymentMethodService $paymentMethodService,
        PayPalPaymentGateway $payPalPaymentGateway,
        PermissionService $permissionService,
        UserProviderInterface $userProvider
    ) {
        parent::__construct();

        $this->currencyService = $currencyService;
        $this->entityManager = $entityManager;
        $this->jsonApiHydrator = $jsonApiHydrator;
        $this->paymentMethodService = $paymentMethodService;
        $this->payPalPaymentGateway = $payPalPaymentGateway;
        $this->permissionService = $permissionService;
        $this->stripePaymentGateway = $stripePaymentGateway;

        $this->paymentMethodRepository = $this->entityManager
                                    ->getRepository(PaymentMethod::class);

        $this->userPaymentMethodsRepository = $this->entityManager
                                    ->getRepository(UserPaymentMethods::class);

        $this->userProvider = $userProvider;

        $this->middleware(ConfigService::$middleware);
    }

    /**
     * Call the service method to create a new payment method based on request parameters.
     * Return - NotFoundException if the request method type parameter it's not defined (paypal or credit card)
     *        - JsonResponse with the new created payment method
     *
     * @param PaymentMethodCreateRequest $request
     *
     * @return JsonResponse|NotFoundException
     *
     * @throws \Railroad\Permissions\Exceptions\NotAllowedException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function store(PaymentMethodCreateRequest $request)
    {
        $userId = auth()->id();

        if ($this->permissionService->can(auth()->id(), 'create.payment.method')) {

            $userId = $request->get('user_id');
        }

        /**
         * @var $user \Railroad\Ecommerce\Contracts\UserInterface
         */
        $user = $this->userProvider->getUserById($userId);

        /**
         * @var $user \Railroad\Usora\Entities\User
         */

        try {
            if (
                $request->get('method_type') ==
                PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE
            ) {
                $customer = $this->stripePaymentGateway->getOrCreateCustomer(
                    $request->get('gateway'),
                    $user->getEmail()
                );

                $card = $this->stripePaymentGateway->createCustomerCard(
                    $request->get('gateway'),
                    $customer,
                    $request->get('card_token')
                );

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

                /**
                 * @var $paymentMethod \Railroad\Ecommerce\Entities\PaymentMethod
                 */

                $paymentMethod = $this->paymentMethodService
                    ->createUserCreditCard(
                        $user,
                        $card->fingerprint,
                        $card->last4,
                        $card->name,
                        $card->brand,
                        $card->exp_year,
                        $card->exp_month,
                        $card->id,
                        $card->customer,
                        $request->get('gateway'),
                        null,
                        $billingAddress,
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
     * @throws \Railroad\Ecommerce\Exceptions\PaymentFailedException
     *
     * @return \Illuminate\Http\RedirectResponse | JsonResponse
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
             * @var $user \Railroad\Ecommerce\Contracts\UserInterface
             */
            $user = $this->userProvider->getCurrentUser();

            $billingAddress = new Address();

            $billingAddress
                ->setType(CartAddressService::BILLING_ADDRESS_TYPE)
                ->setBrand(ConfigService::$brand)
                ->setUser($user)
                ->setState('')
                ->setCountry('');

            $this->entityManager->persist($billingAddress);

            /**
             * @var $paymentMethod \Railroad\Ecommerce\Entities\PaymentMethod
             */
            $paymentMethod = $this->paymentMethodService
                ->createPayPalBillingAgreement(
                    $user,
                    $billingAgreementId,
                    $billingAddress,
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
     * @throws \Railroad\Permissions\Exceptions\NotAllowedException
     * @throws \Railroad\Ecommerce\Exceptions\NotFoundException
     * @return \Illuminate\Http\JsonResponse
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

        $paymentMethod = $userPaymentMethod->getPaymentMethod();

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
     * @param integer $paymentMethodId
     * @return \Illuminate\Http\JsonResponse|NotFoundException
     * @throws \Railroad\Permissions\Exceptions\NotAllowedException
     * @throws \Railroad\Ecommerce\Exceptions\NotFoundException | \Throwable
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
        $qb = $this->entityManager
            ->getRepository(UserPaymentMethods::class)
            ->createQueryBuilder('upm');

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
             * @var $method CreditCard
             */
            $method = $this->entityManager
                ->getRepository(CreditCard::class)
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

            $address = $this->entityManager
                            ->getRepository(Address::class)
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
     * @param integer $paymentMethodId
     *
     * @return JsonResponse
     *
     * @throws NotFoundException - if the payment method not exist
     * @throws NotAllowedException - if the payment method is primary
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
        $qb = $this->entityManager
            ->getRepository(UserPaymentMethods::class)
            ->createQueryBuilder('upm');

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
     * @param integer $userId
     * @return JsonResponse
     */
    public function getUserPaymentMethods($userId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.user.payment.method');

        /**
         * @var $user \Railroad\Ecommerce\Contracts\UserInterface
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
        $qb = $this->entityManager
            ->getRepository(UserPaymentMethods::class)
            ->createQueryBuilder('upm');

        $userPaymentMethods = $qb
            ->select(['upm', 'pm'])
            ->join('upm.paymentMethod', 'pm')
            ->where($qb->expr()->eq('upm.user', ':user'))
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $creditCardIds = [];
        $paypalIds = [];

        foreach ($userPaymentMethods as $userPaymentMethod) {

            $type = $userPaymentMethod
                        ->getPaymentMethod()
                        ->getMethodType();

            if ($type == PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE) {
                $paypalIds[] = $userPaymentMethod
                        ->getPaymentMethod()
                        ->getMethodId();
            } else {
                $creditCardIds[] = $userPaymentMethod
                        ->getPaymentMethod()
                        ->getMethodId();
            }
        }

        $creditCardsMap = $this->entityManager
            ->getRepository(CreditCard::class)
            ->getCreditCardsMap($creditCardIds);

        $paypalAgreementsMap = $this->entityManager
            ->getRepository(PaypalBillingAgreement::class)
            ->getPaypalAgreementsMap($paypalIds);

        return ResponseService::userPaymentMethods(
            $userPaymentMethods,
            $creditCardsMap,
            $paypalAgreementsMap
        );
    }
}
