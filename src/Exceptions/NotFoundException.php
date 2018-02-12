<?php
namespace Railroad\Ecommerce\Exceptions;

class NotFoundException extends \Exception
{
    protected $message;

    /**
     * NotFoundException constructor.
     * @param string $message
     */
    public function __construct($message)
    {
        $this->message = $message;
    }

    public function render($request){

        return response()->json(
            [
                'status' => 'error',
                'code' => 404,
                'total_results' => 0,
                'results' => [],
                'error' => [
                    'title' => 'Product not found.',
                    'detail' => $this->message,
                ]
            ],
            404
        );
    }
}