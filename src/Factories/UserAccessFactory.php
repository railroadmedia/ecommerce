<?php

namespace Railroad\Ecommerce\Factories;


use Faker\Generator;
use Railroad\Permissions\Services\UserAccessService;

class UserAccessFactory extends UserAccessService
{
    /**
     * @var Generator
     */
    protected $faker;

    public function assignAccessToUser($abilityId, $userId)
    {

        $this->faker = app(Generator::class);

        $parameters =
            func_get_args() + [
                rand(), rand()
            ];
        return parent::assignAccessToUser(...$parameters);
    }


}