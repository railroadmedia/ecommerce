<?php

namespace Railroad\Ecommerce\Controllers;


use App\Modules\UserManagementSystem\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Services\SubscriptionUpgradeService;

class SubscriptionUpgradeController extends Controller
{
    private $subscriptionUpgradeService;

    public function __construct(SubscriptionUpgradeService $subscriptionUpgradeService, UserService)
    {
        $this->subscriptionUpgradeService = $subscriptionUpgradeService;
    }

    public function upgrade()
    {
        $user = auth()->id();
        $this->subscriptionUpgradeService->upgrade($user);
    }

    public function upgradeRate()
    {
        $user = auth()->id();
        return $this->subscriptionUpgradeService->getUpgradeRate($user);
    }

    public function downgrade()
    {
        $user = auth()->id();
        $this->subscriptionUpgradeService->downgrade($user);
    }
}
