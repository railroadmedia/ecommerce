<?php

namespace Railroad\Ecommerce\Commands;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Railroad\Ecommerce\ExternalHelpers\CurrencyConversion;
use Railroad\Ecommerce\Services\SubscriptionUpgradeService;
use Throwable;

class TestUpgrade extends Command
{

    protected $name = 'TestUpgrade';
    protected $signature = 'TestUpgrade {d} {userId}';

    protected $description = '';

    public function handle(SubscriptionUpgradeService $service)
    {
        $userId = $this->argument('userId');
        $d = $this->argument('d');
        Auth::loginUsingId($userId);
        
        if ($d == "u") {
            $result = $service->upgrade($userId);
        } elseif ($d == "d") {
            $result = $service->downgrade($userId);
        }
        $this->info($result);
    }
}
