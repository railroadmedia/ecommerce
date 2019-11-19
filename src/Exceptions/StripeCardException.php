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
        $user = auth()->user();

        return response()->json(
            [
                'errors' => [
                    [
                        'title' => 'Unprocessable Card.',
                        'detail' => $this->stripeError
                    ]
                ],
                'meta' => [
                    'user' => $user ? [
                        'id' => $user->getAuthIdentifier()
                    ] : null,
                ]
            ],
            422
        );
    }
}
