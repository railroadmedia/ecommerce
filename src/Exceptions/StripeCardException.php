<?php

namespace Railroad\Ecommerce\Exceptions;

class StripeCardException extends \Exception
{
    protected $stripeError;

    /**
     * StripeCardException constructor.
     * @param array $stripeError
     */
    public function __construct($stripeError)
    {
        $this->stripeError = $stripeError;
    }

    public function render()
    {
        return response()->json(
            [
                'errors' => [
                    'title' => 'Unprocessable Card.',
                    'detail' => $this->stripeError
                ]
            ],
            422
        );
    }
}
