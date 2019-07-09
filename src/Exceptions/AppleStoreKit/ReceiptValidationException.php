<?php

namespace Railroad\Ecommerce\Exceptions\AppleStoreKit;

use Exception;

class ReceiptValidationException extends Exception
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
                        'title' => 'Receipt validation failed.',
                        'detail' => $this->message,
                    ]
                ],
            ],
            404
        );
    }
}
