<?php

namespace Railroad\Ecommerce\Requests;

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
        $rules = [
            'payment_method_type' => 'required_without:payment-method-id',
            'payment-method-id' => 'required_without:payment_method_type',
            'billing-country' => 'required|regex:/^(?!Country$)/',
            'card-token' => 'required_if:payment_method_type,' . PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'gateway' => 'required',
        ];

        if (request()->get('billing-country') == 'Canada') {
            $rules += [
                'billing-region' => 'required|regex:/^[0-9a-zA-Z-_ ]+$/',
                'billing-zip-or-postal-code' => 'required|regex:/^[0-9a-zA-Z-_ ]+$/',
            ];
        }
        $this->cartService->setBrand(request()->get('brand') ?? ConfigService::$brand);
        $cartItems = $this->cartService->getAllCartItems();
        $requiresShippingAddess = in_array(1, array_pluck($cartItems, 'requiresShippingAddress'));

        if ($requiresShippingAddess) {
            $rules += [
                'shipping-address-id' => 'required_without_all:shipping-first-name,shipping-last-name,shipping-address-line-1,shipping-city,shipping-region,shipping-zip-or-postal-code,shipping-country|exists:' .
                    ConfigService::$databaseConnectionName .
                    '.' .
                    ConfigService::$tableAddress .
                    ',id',
                'shipping-first-name' => 'required_without:shipping-address-id|regex:/^[a-zA-Z-_\' ]+$/',
                'shipping-last-name' => 'required_without:shipping-address-id|regex:/^[a-zA-Z-_\' ]+$/',
                'shipping-address-line-1' => 'required_without:shipping-address-id',
                'shipping-city' => 'required_without:shipping-address-id|regex:/^[a-zA-Z-_ ]+$/',
                'shipping-region' => 'required_without:shipping-address-id|regex:/^[0-9a-zA-Z-_ ]+$/',
                'shipping-zip-or-postal-code' => 'required_without:shipping-address-id|regex:/^[0-9a-zA-Z-_ ]+$/',
                'shipping-country' => 'required_without:shipping-address-id|regex:/^(?!Country$)/',
            ];

            if (!auth()->user()) {
                $rules += [
                    'billing-email' => 'required_without:account-creation-email|email',
                    'account-creation-email' => 'required_without:billing-email|email',
                    'account-creation-password' => 'required_with:account-creation-email',
                ];
            }
        }

        return $rules;
    }
}
