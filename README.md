Ecommerce
========================================================================================================================

## Install

1. Install via composer: 
> composer require railroad/ecommerce:2.0
2. Add service provider to your application laravel config app.php file:

```php
'providers' => [
    
    // ... other providers

    Railroad\Ecommerce\Providers\EcommerceServiceProvider::class,
],
```

3. Publish the ecommerce config file: 
> php artisan vendor:publish
4. Fill the ecommerce.php config file:

## API Docs

LINK