<?php

namespace Railroad\Ecommerce\Providers;

interface UserProviderInterface
{
    /**
     * @param $email
     * @param $password
     * @param $displayName
     * @return mixed
     */
    public function create($email, $password, $displayName);
}