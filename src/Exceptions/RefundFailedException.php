<?php

namespace Railroad\Ecommerce\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class RefundFailedException extends Exception
{
    protected $message;

    /**
     * RefundFailedException constructor.
     *
     * @param string $message
     */
    public function __construct(string $message)
    {
        parent::__construct($message);

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
                        'title' => 'Payment refund failed.',
                        'detail' => $this->message,
                    ]
                ],
            ],
            400
        );
    }
}
