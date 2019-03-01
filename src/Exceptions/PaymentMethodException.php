<?php
namespace Railroad\Ecommerce\Exceptions;

use Exception;

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
     * @param $request
     * @return JsonResponse
     */
    public function render($request)
    {
        return response()->json(
            [
                'errors' => [
                    'title' => 'Payment failed.',
                    'detail' => $this->message,
                ],
            ],
            404
        );
    }
}