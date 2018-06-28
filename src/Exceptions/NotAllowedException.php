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
        return reply()->json([],
            [
                'code' => 403,
                'errors' => [
                    'title' => 'Not allowed.',
                    'detail' => $this->message
                ]
            ]);
    }

}