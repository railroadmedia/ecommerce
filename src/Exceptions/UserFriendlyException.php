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

    /**
     * Render the exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        return response()->json([
            'friendlyMessage' => $this->getMessage()
        ], 500);
    }
}
