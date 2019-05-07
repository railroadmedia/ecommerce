<?php

namespace Railroad\Ecommerce\Exceptions\Cart;

use Exception;

class UpdateNumberOfPaymentsCartException extends Exception
{
    /**
     * UpdateNumberOfPaymentsCartException constructor.
     *
     * @param int $numberOfPayments
     */
    public function __construct(int $numberOfPayments)
    {
        parent::__construct(
            'Invalid number of payments to set: ' . $numberOfPayments,
            1
        );
    }

}
