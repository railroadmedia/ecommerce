<?php

namespace Railroad\Ecommerce\Services;

use Railroad\DoctrineArrayHydrator\JsonApiHydrator as BaseHydrator;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

class JsonApiHydrator extends BaseHydrator
{
    /**
     * JsonApiHydrator constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct($em);
    }
}
