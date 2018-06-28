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
        return reply()->json([],
            [
                'code' => 422,
                'errors' => [
                    'title' => 'Unprocessable Entity.',
                    'detail' => $this->message
                ]
            ]);
    }

}