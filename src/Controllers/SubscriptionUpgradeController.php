<?php

namespace Railroad\Ecommerce\Controllers;


use App\Modules\UserManagementSystem\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Services\SubscriptionUpgradeService;

class SubscriptionUpgradeController extends Controller
{
    private SubscriptionUpgradeService $subscriptionUpgradeService;
    private OrderFormJsonController $orderFormJsonController;

    public function __construct(
        SubscriptionUpgradeService $subscriptionUpgradeService,
        OrderFormJsonController $orderFormJsonController
    ) {
        $this->subscriptionUpgradeService = $subscriptionUpgradeService;
        $this->orderFormJsonController = $orderFormJsonController;
    }

    public function upgrade()
    {
        $userId = auth()->id();
        try {
            $message = $this->subscriptionUpgradeService->upgrade($userId);
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }
        return $message;
    }

    public function upgradeRate()
    {
        $userId = auth()->id();
        return $this->subscriptionUpgradeService->getUpgradeRate($userId);
    }

    public function downgrade()
    {
        $userId = auth()->id();
        try {
            $message = $this->subscriptionUpgradeService->downgrade($userId);
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }
        return $message;
    }
}
