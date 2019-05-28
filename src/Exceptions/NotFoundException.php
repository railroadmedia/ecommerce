<?php

namespace Railroad\Ecommerce\Exceptions;

use Illuminate\Http\JsonResponse;

class NotFoundException extends \Exception
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
                        'title' => 'Not found.',
                        'detail' => $this->message,
                    ]
                ],
            ],
            404
        );
    }
}