<?php

namespace Railroad\Ecommerce\Requests;

use Railroad\Ecommerce\Services\CartService;
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
            'payment_method_type' => 'required',
            'billing-country'     => 'required|regex:/^(?!Country$)/',
            'card-token'          => 'required_if:payment_method_type,' .
                PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'gateway'             => 'required'
        ];

        if(request()->get('billing-country') == 'Canada')
        {
            $rules += [
                'billing-region'             => 'required|regex:/^[0-9a-zA-Z-_ ]+$/',
                'billing-zip-or-postal-code' => 'required|regex:/^[0-9a-zA-Z-_ ]+$/'
            ];
        }

        $cartItems              = $this->cartService->getAllCartItems();
        $requiresShippingAddess = in_array(1, array_pluck($cartItems, 'requiresShippingAddress'));

        if($requiresShippingAddess)
        {
            $rules += [
                'shipping-first-name'     => 'required|regex:/^[a-zA-Z-_\' ]+$/',
                'shipping-last-name'      => 'required|regex:/^[a-zA-Z-_\' ]+$/',
                'shipping-address-line-1' => 'required',
                'shipping-city'           => 'required|regex:/^[a-zA-Z-_ ]+$/',
                'shipping-region'         => 'required|regex:/^[0-9a-zA-Z-_ ]+$/',
                'shipping-zip'            => 'required|regex:/^[0-9a-zA-Z-_ ]+$/',
                'shipping-country'        => 'required|regex:/^(?!Country$)/'
            ];

            if(!auth()->user())
            {
                $rules += [
                    'billing-email' => 'required|email'
                ];
            }
        }

        return $rules;
    }
}