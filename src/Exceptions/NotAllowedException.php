<?php
namespace Railroad\Ecommerce\Exceptions;

class NotAllowedException extends \Exception
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
                'code' => 403,
                'total_results' => 0,
                'results' => [],
                'error' => [
                    'title' => 'Not allowed.',
                    'detail' => $this->message,
                ]
            ],
            403
        );
    }

}