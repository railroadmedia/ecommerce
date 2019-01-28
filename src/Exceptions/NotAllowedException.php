<?php

namespace Railroad\Ecommerce\Exceptions;

use Illuminate\Http\JsonResponse;

class NotAllowedException extends \Exception
{
    protected $message;

    /**
     * NotAllowedException constructor.
     *
     * @param string $message
     */
    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * @param $request
     *
     * @return JsonResponse
     */
    public function render($request)
    {
        return response()->json(
            [
                'errors' => [
                    'title' => 'Not allowed.',
                    'detail' => $this->message,
                ],
            ],
            403
        );
    }

}
