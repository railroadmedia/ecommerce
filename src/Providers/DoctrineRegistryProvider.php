<?php

namespace Railroad\Ecommerce\Providers;

use Illuminate\Support\ServiceProvider;

class DoctrineRegistryProvider extends ServiceProvider
{
    public function boot()
    {
        // should check if other package registered the registery in current container
        $this->app->singleton('registry', function ($app) {

            $registry = new IlluminateRegistry($app, $app->make(EntityManagerFactory::class));

            // Add all managers into the registry

            return $registry;
        });
    }
}