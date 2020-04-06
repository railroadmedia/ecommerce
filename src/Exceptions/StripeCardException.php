<?php

namespace Railroad\Ecommerce\Exceptions;

use Exception;

class StripeCardException extends Exception
{
    protected $stripeError;

    /**
     * StripeCardException constructor.
     *
     * @param string $stripeError
     */
    public function __construct(string $stripeError)
    {
        parent::__construct($stripeError);

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
