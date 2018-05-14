<?php

namespace Railroad\Ecommerce\Faker;

use Faker\Factory as FactoryBase;

class Factory extends FactoryBase
{
    public static function create($locale = FactoryBase::DEFAULT_LOCALE)
    {
        $generator = new Faker();

        foreach (static::$defaultProviders as $provider) {
            $providerClassName = self::getProviderClassname($provider, $locale);
            $generator->addProvider(new $providerClassName($generator));
        }

        return $generator;
    }
}