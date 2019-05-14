<?php

namespace Railroad\Ecommerce\Requests;

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
     */
    public function rules()
    {
        // base rules
        $rules = [
            'payment_method_type' => 'required_without:payment_method_id',
            'payment_method_id' => 'required_without:payment_method_type',
            'billing_country' => 'required|in:' . implode(',', config('location.countries')),
            'card_token' => 'required_if:payment_method_type,' . PaymentMethod::TYPE_CREDIT_CARD,
            'gateway' => 'required',
            'currency' => 'in:' . implode(',', config('ecommerce.supported_currencies')),
            'payment_plan_number_of_payments' => 'in:' . implode(',', config('ecommerce.payment_plan_options')),
        ];

        // billing address
        // todo: validate billing address

        // if the country is in canada we must also get the region and zip
        if (request()->get('billing_country') == 'Canada') {
            $rules += [
                'billing_region' => 'required|regex:/^[0-9a-zA-Z-_ ]+$/',
                'billing_zip_or_postal_code' => 'regex:/^[0-9a-zA-Z-_ ]+$/',
            ];
        }

        // if the cart has any items that need shipping
        if ($this->shippingService->doesCartHaveAnyPhysicalItems($this->cartService->getCart())) {
            $rules += [
                'shipping_address_id' => 'required_without_all:shipping_first_name,shipping_last_name,shipping_address_line_1,shipping_city,shipping_region,shipping_zip_or_postal_code,shipping_country|exists:' .
                    config('ecommerce.database_connection_name') .
                    '.' .
                    'ecommerce_addresses' .
                    ',id',
                'shipping_first_name' => 'required_without:shipping_address_id|regex:/^[a-zA-Z-_\' ]+$/',
                'shipping_last_name' => 'required_without:shipping_address_id|regex:/^[a-zA-Z-_\' ]+$/',
                'shipping_address_line_1' => 'required_without:shipping_address_id',
                'shipping_city' => 'required_without:shipping_address_id|regex:/^[a-zA-Z-_ ]+$/',
                'shipping_region' => 'required_without:shipping_address_id|regex:/^[0-9a-zA-Z-_ ]+$/',
                'shipping_zip_or_postal_code' => 'required_without:shipping_address_id|regex:/^[0-9a-zA-Z-_ ]+$/',
                'shipping_country' => 'required_without:shipping_address_id|in:' .
                    implode(',', config('location.countries')),
            ];
        }

        // user/customer rules
        if (!auth()->user()) {
            if (!$this->shippingService->doesCartHaveAnyDigitalItems($this->cartService->getCart())) {
                $rules += [
                    'billing_email' => 'required_without:account_creation_email|email',
                ];
            }
            else {
                $rules += [
                    'account_creation_email' => 'required_without:billing_email|email',
                    'account_creation_password' => 'required_with:account_creation_email|confirmed',
                ];
            }
        }

        return $rules;
    }

    /**
     * @return Cart
     */
    public function getCart()
    {
        $cart = Cart::fromSession();

        $cart->setPaymentPlanNumberOfPayments($this->get('payment_plan_number_of_payments', 1));

        $cart->setShippingAddress($this->getShippingAddressStructure());
        $cart->setBillingAddress($this->getBillingAddressStructure());

        $cart->setBillingAddressId($this->get('billing_address_id'));
        $cart->setShippingAddressId($this->get('shipping_address_id'));
        $cart->setPaymentMethodId($this->get('payment_method_id'));
        $cart->setCurrency($this->get('currency', config('ecommerce.default_currency')));

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

    protected function populateShippingAddress(AddressInterface $address)
    {
        $address->setFirstName($this->get('shipping_first_name'));
        $address->setLastName($this->get('shipping_last_name'));
        $address->setStreetLine1($this->get('shipping_address_line_1'));
        $address->setStreetLine2($this->get('shipping_address_line_2'));
        $address->setCity($this->get('shipping_city'));
        $address->setState($this->get('shipping_region'));
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
        $purchaser->setBrand(config('ecommerce.brand'));

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

    protected function populateBillingAddress(AddressInterface $address)
    {
        $address->setFirstName($this->get('billing_first_name'));
        $address->setLastName($this->get('billing_last_name'));
        $address->setStreetLine1($this->get('billing_address_line_1'));
        $address->setStreetLine2($this->get('billing_address_line_2'));
        $address->setCity($this->get('billing_city'));
        $address->setState($this->get('billing_region'));
        $address->setCountry($this->get('billing_country'));
        $address->setZip($this->get('billing_zip_or_postal_code'));

        return $address;
    }
}
