<?php


namespace Railroad\Ecommerce\Factories;


use Faker\Generator;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Permissions\Services\AccessService;

class AccessFactory extends AccessService
{
    /**
     * @var Generator
     */
    protected $faker;

    public function store($name='', $slug='',  $description = '', $brand = null)
    {

        $this->faker = app(Generator::class);

        $parameters =
            func_get_args() + [
                $this->faker->word,
                $this->faker->slug, $this->faker->text,
                ConfigService::$brand
            ];
        return parent::store(...$parameters);
    }
}