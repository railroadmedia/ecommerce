<?php

namespace Railroad\Ecommerce\Requests;

use Doctrine\ORM\ORMException;
use Exception;
use Illuminate\Contracts\Validation\Validator;
use Railroad\Ecommerce\Contracts\Address as AddressInterface;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Structures\Address as AddressStructure;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\Purchaser;
use Railroad\Ecommerce\Repositories\AddressRepository;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ShippingService;
use Railroad\Permissions\Services\PermissionService;
use Throwable;

/**
 * Class OrderFormSubmitRequest
 * @package Railroad\Ecommerce\Requests
 */
class OrderFormSubmitRequest extends FormRequest
{
    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var ShippingService
     */
    protected $shippingService;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * @var AddressRepository
     */
    private $addressRepository;

    /**
     * OrderFormSubmitRequest constructor.
     * @param CartService $cartService
     * @param ShippingService $shippingService
     * @param PermissionService $permissionService
     * @param UserProviderInterface $userProvider
     * @param AddressRepository $addressRepository
     */
    public function __construct(
        CartService $cartService,
        ShippingService $shippingService,
        PermissionService $permissionService,
        UserProviderInterface $userProvider,
        AddressRepository $addressRepository
    )
    {
        parent::__construct();

        $this->cartService = $cartService;
        $this->shippingService = $shippingService;
        $this->permissionService = $permissionService;

        $this->cartService->refreshCart();
        $this->userProvider = $userProvider;
        $this->addressRepository = $addressRepository;
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * @return Validator
     */
    public function getValidatorInstance()
    {
        // if this request is from a paypal redirect we must merge in the old input
        if (!empty($this->get('token'))) {

            $orderFormInput = session()->get('order-form-input', []);
            unset($orderFormInput['token']);
            session()->forget('order-form-input');
            $this->merge($orderFormInput);
        }

        return parent::getValidatorInstance();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     *
     * @throws ORMException
     * @throws Throwable
     */
    public function rules()
    {
        // base rules
        $rules = [
            'brand' => 'string',
            'payment_method_type' => 'string|required_without:payment_method_id',
            'payment_method_id' => 'integer|required_without:payment_method_type',
            'billing_country' => 'string|required_without:payment_method_id|in:' .
                implode(',', config('location.countries')),
            'card_token' => 'string|required_if:payment_method_type,' . PaymentMethod::TYPE_CREDIT_CARD,
            'gateway' => 'string|required_without:payment_method_id',
            'currency' => 'string|in:' . implode(',', config('ecommerce.supported_currencies')),
        ];

        if (!$this->cartService->hasAnyRecurringSubscriptionProducts()) {
            $rules['payment_plan_number_of_payments'] =
                'integer|in:' . implode(',', config('ecommerce.payment_plan_options'));
        }
        else {
            $rules['payment_plan_number_of_payments'] = 'integer|in:1';
        }

        // billing address
        $rules += [
            'billing_country' => 'string|required|in:' .
                    implode(',', config('location.countries')),
        ];

        // if the country is in canada we must also get the region and zip
        if (request()->get('billing_country') == 'Canada') {
            $rules += [
                'billing_region' => 'string|required|regex:/^[0-9a-zA-Z-_ ]+$/|in:' .
                    implode(',', config('location.country_regions.Canada')),
                'billing_zip_or_postal_code' => 'string|regex:/^[0-9a-zA-Z-_ ]+$/',
            ];
        }

        // if the cart has any items that need shipping
        if ($this->shippingService->doesCartHaveAnyPhysicalItems($this->cartService->getCart())) {
            $rules += [
                'shipping_address_id' => 'integer|required_without_all:shipping_first_name,shipping_last_name,shipping_address_line_1,shipping_city,shipping_region,shipping_zip_or_postal_code,shipping_country|exists:' .
                    config('ecommerce.database_connection_name') .
                    '.' .
                    'ecommerce_addresses' .
                    ',id',
                'shipping_first_name' => 'string|required_without:shipping_address_id|regex:/^[a-zA-Z-_\' ]+$/',
                'shipping_last_name' => 'string|required_without:shipping_address_id|regex:/^[a-zA-Z-_\' ]+$/',
                'shipping_address_line_1' => 'string|required_without:shipping_address_id|regex:/^[0-9a-zA-Z-_.\' ]+$/',
                'shipping_address_line_2' => 'nullable|string|regex:/^[0-9a-zA-Z-_.\' ]+$/',
                'shipping_city' => 'string|required_without:shipping_address_id|regex:/^[a-zA-Z-_ ]+$/',
                'shipping_region' => 'string|required_without:shipping_address_id|regex:/^[0-9a-zA-Z-_ ]+$/',
                'shipping_zip_or_postal_code' => 'string|required_without:shipping_address_id|regex:/^[0-9a-zA-Z-_ ]+$/',
                'shipping_country' => 'string|required_without:shipping_address_id|in:' .
                    implode(',', config('location.countries')),
            ];
        }

        // user/customer rules
        if (!auth()->user()) {
            if (!$this->shippingService->doesCartHaveAnyDigitalItems($this->cartService->getCart()) &&
                empty($this->get('account_creation_email')) &&
                !empty($this->get('billing_email'))) {
                $rules += [
                    'billing_email' => 'required_without:account_creation_email|email',
                ];
            }
            else {
                /*
                 * password validation rules exist in four locations:
                 * 1. account creation, by user: \Railroad\Ecommerce\Requests\OrderFormSubmitRequest::rules
                 * 2. password change, by user: \Railroad\Usora\Controllers\PasswordController::update
                 * 3. reset forgotten password, by user: \Railroad\Usora\Controllers\ResetPasswordController::reset
                 * 4. reset user's password, by staff: \Railroad\Usora\Requests\UserJsonUpdateRequest::rules
                 */
                $rules += [
                    'account_creation_email' => 'required_without:billing_email|email|unique:' .
                        config('ecommerce.database_info_for_unique_user_email_validation.database_connection_name') .
                        '.' .
                        config('ecommerce.database_info_for_unique_user_email_validation.table') .
                        ',' .
                        config('ecommerce.database_info_for_unique_user_email_validation.email_column'),
                    'account_creation_password' => 'required_with:account_creation_email|min:8|max:128',
                ];
            }
        }

        $this->cartService->setCart($this->getCart());

        // its a free empty payment
        if ($this->cartService->getDueForInitialPayment() == 0) {
            unset($rules['payment_method_type']);
            unset($rules['payment_method_id']);
            unset($rules['billing_country']);
            unset($rules['card_token']);
            unset($rules['currency']);
            unset($rules['billing_region']);
            unset($rules['billing_zip_or_postal_code']);
            unset($rules['gateway']);

            $rules['payment_plan_number_of_payments'] = 'integer|in:1';
        }

        return $rules;
    }

    public function messages()
    {
        $msg = 'Only English alphabet characters are supported for the shipping address';

        return [
            'shipping_first_name.regex'         => $msg,
            'shipping_last_name.regex'          => $msg,
            'shipping_address_line_1.regex'     => $msg,
            'shipping_address_line_2.regex'     => $msg,
            'shipping_city.regex'               => $msg,
            'shipping_region.regex'             => $msg,
            'shipping_zip_or_postal_code.regex' => $msg,
            'shipping_country.regex'            => $msg,
        ];
    }

    /**
     * @return Cart
     *
     * @throws ORMException
     */
    public function getCart()
    {
        $cart = Cart::fromSession();

        $cart->setPaymentPlanNumberOfPayments($this->get('payment_plan_number_of_payments', 1));

        $cart->setShippingAddress(
            $this->getShippingAddress()
                ->toStructure()
        );
        $cart->setBillingAddress(
            $this->getBillingAddress()
                ->toStructure()
        );

        $cart->setBillingAddressId($this->get('billing_address_id'));
        $cart->setShippingAddressId($this->get('shipping_address_id'));
        $cart->setPaymentMethodId($this->get('payment_method_id'));
        $cart->setCurrency($this->get('currency', config('ecommerce.default_currency')));

        if ($this->permissionService->can(auth()->id(), 'place-orders-for-other-users')) {

            if ($this->has('shipping_due_override')) {
                $cart->setShippingOverride($this->get('shipping_due_override'));
            }

            if ($this->has('order_items_due_overrides')) {
                $overrides = $this->get('order_items_due_overrides', []);

                if (!empty($overrides) && is_array($overrides)) {
                    foreach ($overrides as $override) {
                        foreach ($cart->getItems() as $cartItem) {
                            if ($override['sku'] == $cartItem->getSku() && !is_null($override['amount'])) {
                                $cartItem->setDueOverride($override['amount']);
                            }
                        }
                    }
                }
            }
        }

        return $cart;
    }

    /**
     * @return AddressStructure
     */
    public function getShippingAddressStructure()
    {
        return $this->populateShippingAddress(new AddressStructure());
    }

    /**
     * @return Address
     *
     * @throws ORMException
     */
    public function getShippingAddress()
    {
        $address = null;

        if (!empty($this->get('shipping_address_id'))) {
            $address = $this->addressRepository->byId($this->get('shipping_address_id'));
        }

        if (empty($address)) {
            $address = $this->populateShippingAddress(new Address());
            $address->setType(Address::SHIPPING_ADDRESS_TYPE);
        }

        return $address;
    }

    /**
     * @param AddressInterface $address
     * @return AddressInterface
     */
    protected function populateShippingAddress(AddressInterface $address)
    {
        $address->setFirstName($this->get('shipping_first_name'));
        $address->setLastName($this->get('shipping_last_name'));
        $address->setStreetLine1($this->get('shipping_address_line_1'));
        $address->setStreetLine2($this->get('shipping_address_line_2'));
        $address->setCity($this->get('shipping_city'));
        $address->setRegion($this->get('shipping_region'));
        $address->setCountry($this->get('shipping_country'));
        $address->setZip($this->get('shipping_zip_or_postal_code'));

        return $address;
    }

    /**
     * @return AddressStructure
     */
    public function getBillingAddressStructure()
    {
        return $this->populateBillingAddress(new AddressStructure());
    }

    /**
     * @return Address
     *
     * @throws ORMException
     */
    public function getBillingAddress()
    {
        $address = null;

        if (!empty($this->get('billing_address_id'))) {
            $address = $this->addressRepository->byId($this->get('billing_address_id'));
        }

        if (empty($address)) {
            $address = $this->populateBillingAddress(new Address());
            $address->setType(Address::BILLING_ADDRESS_TYPE);
        }

        return $address;
    }

    /**
     * @return Purchaser
     * @throws Exception
     */
    public function getPurchaser()
    {
        $purchaser = new Purchaser();

        // set the brand
        $purchaser->setBrand($this->get('brand', config('ecommerce.brand')));

        // user with special permissions can place orders for other users
        if ($this->permissionService->can(auth()->id(), 'place-orders-for-other-users') &&
            !empty($this->get('user_id'))) {

            $user = $this->userProvider->getUserById($this->get('user_id'));

            $purchaser->setId($user->getId());
            $purchaser->setEmail($user->getEmail());
            $purchaser->setType(Purchaser::USER_TYPE);
            $purchaser->setBrand($this->get('brand', config('ecommerce.brand')));

            return $purchaser;
        }

        // an existing user
        if (auth()->check()) {
            $user = $this->userProvider->getCurrentUser();

            $purchaser->setId($user->getId());
            $purchaser->setEmail($user->getEmail());
            $purchaser->setType(Purchaser::USER_TYPE);

            return $purchaser;
        }

        // creating a new user
        if (!empty($this->get('account_creation_email')) && !empty($this->get('account_creation_password'))) {
            $purchaser->setEmail($this->get('account_creation_email'));
            $purchaser->setRawPassword($this->get('account_creation_password'));
            $purchaser->setType(Purchaser::USER_TYPE);

            return $purchaser;
        }

        // guest customer
        if (!empty($this->get('billing_email'))) {
            $purchaser->setEmail($this->get('billing_email'));
            $purchaser->setType(Purchaser::CUSTOMER_TYPE);

            return $purchaser;
        }

        throw new Exception('Could not create purchaser for order, there was is no user or customer info submitted.');
    }

    /**
     * @param AddressInterface $address
     * @return AddressInterface
     */
    protected function populateBillingAddress(AddressInterface $address)
    {
        $address->setFirstName($this->get('billing_first_name'));
        $address->setLastName($this->get('billing_last_name'));
        $address->setStreetLine1($this->get('billing_address_line_1'));
        $address->setStreetLine2($this->get('billing_address_line_2'));
        $address->setCity($this->get('billing_city'));
        $address->setRegion($this->get('billing_region'));
        $address->setCountry($this->get('billing_country'));
        $address->setZip($this->get('billing_zip_or_postal_code'));

        return $address;
    }
}
