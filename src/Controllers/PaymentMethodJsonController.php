<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Illuminate\Http\Request;
use Railroad\DoctrineArrayHydrator\JsonApiHydrator;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Events\PaypalPaymentMethodEvent;
use Railroad\Ecommerce\Exceptions\NotAllowedException;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Exceptions\StripeCardException;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
// use Railroad\Ecommerce\Repositories\AddressRepository;
// use Railroad\Ecommerce\Repositories\CreditCardRepository;
// use Railroad\Ecommerce\Repositories\CustomerPaymentMethodsRepository;
// use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
// use Railroad\Ecommerce\Repositories\PaypalBillingAgreementRepository;
// use Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository;
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
use Railroad\Usora\Entities\User;
use Stripe\Error\Card;

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
     * @var \Railroad\Ecommerce\Services\PaymentMethodService
     */
    // private $paymentMethodService;

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
     * @var PaymentMethodRepository
     */
    // private $paymentMethodRepository;

    /**
     * @var UserPaymentMethodsRepository
     */
    // private $userPaymentMethodRepository;

    /**
     * @var CustomerPaymentMethodsRepository
     */
    // private $customerPaymentMethodRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\AddressRepository
     */
    // private $addressRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\CreditCardRepository
     */
    // private $creditCardRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\PaypalBillingAgreementRepository
     */
    // private $paypalBillingAgreementRepository;

    /**
     * PaymentMethodJsonController constructor.
     *
     * @param \Railroad\Permissions\Services\PermissionService                  $permissionService
     * @param \Railroad\Ecommerce\Repositories\PaymentMethodRepository          $paymentMethodRepository
     * @param \Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository     $userPaymentMethodsRepository
     * @param \Railroad\Ecommerce\Repositories\CustomerPaymentMethodsRepository $customerPaymentMethodsRepository
     */
    public function __construct(
        CurrencyService $currencyService,
        EntityManager $entityManager,
        JsonApiHydrator $jsonApiHydrator,
        StripePaymentGateway $stripePaymentGateway,
        PaymentMethodService $paymentMethodService,
        PayPalPaymentGateway $payPalPaymentGateway,
        PermissionService $permissionService
        // PaymentMethodRepository $paymentMethodRepository,
        // UserPaymentMethodsRepository $userPaymentMethodsRepository,
        // CustomerPaymentMethodsRepository $customerPaymentMethodsRepository,
        // AddressRepository $addressRepository,
        // CreditCardRepository $creditCardRepository,
        // PaypalBillingAgreementRepository $paypalBillingAgreementRepository
    ) {
        parent::__construct();

        $this->currencyService = $currencyService;
        $this->entityManager = $entityManager;
        $this->jsonApiHydrator = $jsonApiHydrator;
        $this->paymentMethodService = $paymentMethodService;
        $this->payPalPaymentGateway = $payPalPaymentGateway;
        $this->permissionService = $permissionService;
        $this->stripePaymentGateway = $stripePaymentGateway;

        // $this->paymentMethodRepository          = $paymentMethodRepository;
        // $this->userPaymentMethodRepository      = $userPaymentMethodsRepository;
        // $this->customerPaymentMethodRepository  = $customerPaymentMethodsRepository;
        // $this->addressRepository                = $addressRepository;
        // $this->creditCardRepository             = $creditCardRepository;
        // $this->paypalBillingAgreementRepository = $paypalBillingAgreementRepository;

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

        $userRepository = $this->entityManager->getRepository(User::class);
        $user = null;

        if ($this->permissionService->can(auth()->id(), 'create.payment.method')) {

            /**
             * @var $qb \Doctrine\ORM\QueryBuilder
             */
            $qb = $userRepository->createQueryBuilder('u');

            $user = $qb
                ->where($qb->expr()->eq('u.id', ':id'))
                ->andWhere($qb->expr()->eq('u.email', ':email'))
                ->setParameter('id', $request->get('user_id'))
                ->setParameter('email', $request->get('user_email'))
                ->getQuery()
                ->getSingleResult();

        } else {
            $user = $userRepository->find($userId);
        }

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
             * @var $user \Railroad\Usora\Entities\User
             */
            $user = $this->entityManager
                        ->getRepository(User::class)
                        ->find(auth()->id());

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
        $paymentMethod = $this->paymentMethodRepository
            ->read($request->get('id'));

        if (($paymentMethod['user']['user_id'] ?? 0) !== auth()->id()) {
            $this->permissionService
                ->canOrThrow(auth()->id(), 'update.payment.method');
        }

        $message = 'Update failed, payment method not found with id: '
            . $request->get('id');

        throw_if(is_null($paymentMethod), new NotFoundException($message));

        $this->userPaymentMethodRepository
            ->query()
            ->where('user_id', auth()->id())
            ->update(['is_primary' => false]);

        $userPaymentMethodId = $this->userPaymentMethodRepository
            ->query()
            ->where('user_id', auth()->id())
            ->where('payment_method_id', $paymentMethod['id'])
            ->update(['is_primary' => true]);

        return response()->json([
            'userPaymentMethodId' => $userPaymentMethodId
        ]);
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
            ->read($paymentMethodId);

        if (($paymentMethod['user']['user_id'] ?? 0) !== auth()->id()) {
            $this->permissionService
                ->canOrThrow(auth()->id(), 'update.payment.method');
        }

        $message = 'Update failed, payment method not found with id: '
            . $paymentMethodId;

        throw_if(is_null($paymentMethod), new NotFoundException($message));

        $customer = $this->stripePaymentGateway->getCustomer(
            $request->get('gateway'),
            $paymentMethod->method['external_customer_id']
        );

        $card = $this->stripePaymentGateway->getCard(
            $customer,
            $paymentMethod->method['external_id'],
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
            $card->exp_year,
            $card->exp_month
        )->toDateTimeString();

        $this->creditCardRepository->update(
            $paymentMethod->method['id'],
            [
                'fingerprint'          => $card->fingerprint,
                'last_four_digits'     => $card->last4,
                'cardholder_name'      => $card->name,
                'expiration_date'      => $expirationDate,
                'external_id'          => $card->id,
                'external_customer_id' => $card->customer,
                'payment_gateway_name' => $request->get('gateway'),
                'updated_on'           => Carbon::now()->toDateTimeString(),
            ]
        );

        $billingCountry = $card->address_country ?? $card->country;

        $this->addressRepository->update(
            $paymentMethod->billing_address['id'],
            [
                'state'      => $card->address_state ?? '',
                'country'    => $billingCountry ?? '',
                'updated_on' => Carbon::now()->toDateTimeString(),
            ]
        );

        if ($request->get('set_default')) {

            $this->userPaymentMethodRepository
                ->query()
                ->where('user_id', auth()->id())
                ->update(['is_primary' => false]);

            $this->userPaymentMethodRepository
                ->query()
                ->where('user_id', auth()->id())
                ->where('payment_method_id', $paymentMethodId)
                ->update(['is_primary' => true]);
        }

        $card = $this->creditCardRepository
            ->read($paymentMethod->method['id']);

        return reply()->json($card, [
            'code' => 200
        ]);
    }

    /**
     * Delete a payment method and return a JsonResponse.
     *  Throw  - NotFoundException if the payment method not exist
     *
     * @param integer $paymentMethodId
     * @return JsonResponse
     */
    public function delete($paymentMethodId)
    {
        $paymentMethod = $this->paymentMethodRepository->read($paymentMethodId);

        throw_if(
            is_null($paymentMethod),
            new NotFoundException('Delete failed, payment method not found with id: ' . $paymentMethodId)
        );

        if($paymentMethod['user_id'] !== auth()->id())
        {
            $this->permissionService->canOrThrow(auth()->id(), 'delete.payment.method');
        }

        throw_if(
            $paymentMethod->user['is_primary'],
            new NotAllowedException('Delete failed, can not delete the default payment method')
        );

        $results = $this->paymentMethodRepository->delete($paymentMethodId);

        return reply()->json(null, [
            'code' => 204
        ]);
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

    /** Get all user's payment methods with all the method details: credit card or paypal billing agreement
     *
     * @param integer $userId
     * @return JsonResponse
     */
    public function getUserPaymentMethods($userId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.user.payment.method');

        $paymentMethods = $this->userPaymentMethodRepository->query()->where(['user_id' => $userId])->get();

        foreach ($paymentMethods as $paymentMethod) {
            $paymentMethod['id'] = $paymentMethod['payment_method_id'];
        }

        return reply()->json($paymentMethods);
    }
}