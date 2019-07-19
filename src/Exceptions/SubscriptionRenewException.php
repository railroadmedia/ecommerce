<?php

namespace Railroad\Ecommerce\Exceptions;

use Exception;

class SubscriptionRenewException extends Exception
{
    protected $message;

    /**
     * SubscriptionRenewException constructor.
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
                        'title' => 'Subscription renew failed.',
                        'detail' => $this->message,
                    ]
                ],
            ],
            400
        );
    }
}
