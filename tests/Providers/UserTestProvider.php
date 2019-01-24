<?php

namespace Railroad\Ecommerce\Tests\Providers;

use Illuminate\Support\Facades\DB;
use Railroad\Ecommerce\Providers\UserProviderInterface;
use Railroad\Ecommerce\Services\ConfigService;

class UserTestProvider implements UserProviderInterface
{
    public function __construct()
    {
    }

    public function create($email, $password, $displayName)
    {
        return DB::connection(ConfigService::$databaseConnectionName)
            ->table('users')
            ->insertGetId(
                [
                    'email' => $email,
                    'password' => $password,
                    'display_name' => $displayName,
                ]
            );
    }

}
