<?php

namespace Railroad\Ecommerce\Exceptions;

use Exception;

class UnprocessableEntityException extends Exception
{
    protected $message;

    /**
     * UnprocessableEntityException constructor.
     *
     * @param string $message
     */
    public function __construct(string $message)
    {
        parent::__construct($message);

        $this->message = $message;
    }

    public function render()
    {
        return response()->json(
            [
                'errors' => [
                    [
                        'title' => 'Unprocessable Entity.',
                        'detail' => $this->message,
                    ]
                ],
            ],
            422
        );
    }

}
