<?php

namespace Railroad\Ecommerce\Requests;

use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;

class OrderFormSubmitRequest extends FormRequest
{
    /**
     * @var \Railroad\Ecommerce\Services\CartService
     */
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;

        $this->cartService->refreshCart();
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

            'card_token' => 'required_if:payment_method_type,' . PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
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
                'billing_zip_or_postal_code' => 'required|regex:/^[0-9a-zA-Z-_ ]+$/',
            ];
        }

        // if the cart has any items that need shipping
        if ($this->cartService->cartHasAnyPhysicalItems()) {
            $rules += [
                'shipping_address_id' => 'required_without_all:shipping_first_name,shipping_last_name,shipping_address_line_1,shipping_city,shipping_region,shipping_zip_or_postal_code,shipping_country|exists:'
                    . ConfigService::$databaseConnectionName
                    . '.'
                    . ConfigService::$tableAddress
                    . ',id',
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
            if ($this->cartService->cartHasAnyDigitalItems()) {
                $rules += [
                    'account_creation_email' => 'required|email',
                    'account_creation_password' => 'required|confirmed',
                ];
            } else {
                $rules += [
                    'billing_email' => 'required_without:account_creation_email|email',
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

        $cart->setPaymentPlanNumberOfPayments($this->get('payment_plan_number_of_payments'));
        $cart->setShippingAddress($this->getShippingAddress());
        $cart->setBillingAddress($this->getBillingAddress());

        return $cart;
    }

    /**
     * @return Address
     */
    public function getShippingAddress()
    {
        $address = new Address();

        $address->setFirstName($this->get('shipping_first_name'));
        $address->setLastName($this->get('shipping_last_name'));
        $address->setStreetLineOne($this->get('shipping_address_line_1'));
        $address->setStreetLineTwo($this->get('shipping_address_line_2'));
        $address->setCity($this->get('shipping_city'));
        $address->setState($this->get('shipping_region'));
        $address->setCountry($this->get('shipping_country'));
        $address->setZipOrPostalCode($this->get('shipping_zip_or_postal_code'));

        return $address;
    }

    /**
     * @return Address
     */
    public function getBillingAddress()
    {
        $address = new Address();

        $address->setFirstName($this->get('billing_first_name'));
        $address->setLastName($this->get('billing_last_name'));
        $address->setStreetLineOne($this->get('billing_address_line_1'));
        $address->setStreetLineTwo($this->get('billing_address_line_2'));
        $address->setCity($this->get('billing_city'));
        $address->setState($this->get('billing_region'));
        $address->setCountry($this->get('billing_country'));
        $address->setZipOrPostalCode($this->get('billing_zip_or_postal_code'));

        return $address;
    }
}
