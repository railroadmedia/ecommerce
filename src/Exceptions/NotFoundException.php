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
        return reply()->json([],
            [
                'code' => 404,
                'errors' => [
                    'title' => 'Not found.',
                    'detail' => $this->message
                ]
            ]);
    }
}