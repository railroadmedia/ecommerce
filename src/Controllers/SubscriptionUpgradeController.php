<?php

namespace Railroad\Ecommerce\Controllers;


use App\Modules\Ecommerce\Enums\DigitalAccessType;
use App\Modules\Ecommerce\Models\Product;
use App\Modules\UserManagementSystem\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Services\MembershipTier;
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

    public function change($tier, $interval)
    {
        $userId = auth()->id();
        try {
            switch ($tier) {
                case "plus":
                    $accessType = Product::DIGITAL_ACCESS_TYPE_ALL_CONTENT_ACCESS;
                case "basic":
                    $accessType = Product::DIGITAL_ACCESS_TYPE_BASIC_CONTENT_ACCESS;
            }
            $message = $this->subscriptionUpgradeService->changeSubscription($accessType, $interval, $userId);
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }
        return $message;
    }

    public function info()
    {
        $userId = auth()->id();
        return $this->subscriptionUpgradeService->getUpgradeRate($userId);
    }
}
