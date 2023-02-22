<?php

namespace Railroad\Ecommerce\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class RedirectNeededException extends Exception
{
    protected $message;

    private $urlRedirect;

    /**
     * NotAllowedException constructor.
     *
     * @param string $message
     */
    public function __construct($urlRedirect, $message)
    {
        parent::__construct($message);

        $this->urlRedirect = $urlRedirect;
        $this->message = $message;
    }

    public function getUrlRedirect()
    {
        return $this->urlRedirect;
    }

    // todo: what to do?
}
