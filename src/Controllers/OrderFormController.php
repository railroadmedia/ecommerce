<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\Request;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Services\OrderFormService;
use Railroad\Ecommerce\Services\ConfigService;

class OrderFormController extends BaseController
{
    /**
     * @var \Railroad\Ecommerce\Services\OrderFormService
     */
    private $orderFormService;

    /**
     * OrderFormController constructor.
     *
     * @param OrderFormService $orderFormService
     */
    public function __construct(
        OrderFormService $orderFormService
    ) {
        parent::__construct();

        $this->orderFormService = $orderFormService;
    }

    /**
     * Landing action for paypal agreement redirect
     *
     * @param $request
     * @return JsonResponse
     */
    public function submitPaypalOrder(Request $request)
    {

        //if the cart it's empty; we throw an exception
        throw_if(
            !$request->has('token'),
            new NotFoundException('Invalid request')
        );

        $result = $this->orderFormService->processOrderForm($request);

        if (isset($result['errors']) || !isset($result['order'])) {

            $redirectResponse = isset($result['redirect']) ?
                    redirect()->away($result['redirect']) :
                    redirect()->back();

            foreach ($result['errors'] ?? [] as $message) {
                $redirectResponse->with('error', $message);
            }

        } else {

            $redirectResponse = redirect()->away(
                ConfigService::$paypalAgreementFulfilledRoute
            );

            $redirectResponse->with('success', true);
            $redirectResponse->with('order', $result['order']);
        }

        /** @var \Illuminate\Http\RedirectResponse $redirectResponse */

        return $redirectResponse;
    }
}
