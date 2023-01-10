<?php

namespace Railroad\Ecommerce\Exceptions;

use Exception;

class UserFriendlyException extends Exception
{
    /**
     * Report the exception.
     *
     * @return bool|null
     */
    public function report()
    {
        return false;
    }
}
