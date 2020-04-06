<?php

namespace Railroad\Ecommerce\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class PaymentMethodException extends Exception
{
    protected $message;

    /**
     * NotFoundException constructor.
     *
     * @param string $message
     */
    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * @return JsonResponse
     */
    public function render()
    {
        return response()->json(
            [
                'errors' => [
                    [
                        'title' => 'Payment failed.',
                        'detail' => $this->message,
                    ]
                ],
            ],
            404
        );
    }
}
