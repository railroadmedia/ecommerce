<?php

namespace Railroad\Ecommerce\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class RedirectNeededException extends Exception
{
    protected $urlRedirect;
    protected $redirectMessageToUser;
    protected $messageTitleText;
    protected $buttonText;

    /**
     * NotAllowedException constructor.
     *
     * @param $urlRedirect
     * @param $redirectMessageToUser
     * @param $messageTitleText
     * @param $buttonText
     */
    public function __construct(
        $urlRedirect,
        $redirectMessageToUser,
        $messageTitleText,
        $buttonText
    )
    {
        parent::__construct(
            'Redirect-with-message required with message: "' . $redirectMessageToUser . '".'
        );

        $this->urlRedirect = $urlRedirect;
        $this->redirectMessageToUser = $redirectMessageToUser;
        $this->messageTitleText = $messageTitleText;
        $this->buttonText = $buttonText;
    }

    public function getUrlRedirect()
    {
        return $this->urlRedirect;
    }

    public function getRedirectMessageToUser()
    {
        return $this->redirectMessageToUser;
    }

    public function getMessageTitleText()
    {
        return $this->messageTitleText;
    }

    public function getButtonText()
    {
        return $this->buttonText;
    }
}
