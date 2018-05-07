<?php
namespace Railroad\Ecommerce\Exceptions;

class UnprocessableEntityException extends \Exception
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
                'code' => 422,
                'total_results' => 0,
                'results' => [],
                'error' => [
                    'title' => 'Unprocessable Entity.',
                    'detail' => $this->message,
                ]
            ],
            422
        );
    }

}