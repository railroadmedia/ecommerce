<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Requests\OrderFormSubmitRequest;
use Railroad\Ecommerce\Services\OrderFormService;
use Railroad\Ecommerce\Services\ConfigService;
use Throwable;

class OrderFormController extends Controller
{
    /**
     * @var OrderFormService
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
        $this->orderFormService = $orderFormService;
    }

    /**
     * Landing action for paypal agreement redirect
     *
     * @param $request
     *
     * @return RedirectResponse
     *
     * @throws Throwable
     */
    public function submitPaypalOrder(OrderFormSubmitRequest $request)
    {

        //if the cart it's empty; we throw an exception
        throw_if(
            !$request->has('token'),
            new NotFoundException('Invalid request')
        );

        $result = $this->orderFormService->processOrderFormSubmit($request);

        if (isset($result['errors']) || !isset($result['order'])) {

            $redirectResponse = isset($result['redirect']) ?
                    redirect()->away($result['redirect']) :
                    redirect()->back();

            foreach ($result['errors'] ?? [] as $message) {
                $redirectResponse->with('error', $message);
            }

        } else {

            $redirectResponse = redirect()->route(
                ConfigService::$paypalAgreementFulfilledRoute
            );

            $redirectResponse->with('success', true);
            $redirectResponse->with('order', $result['order']);
        }

        /** @var RedirectResponse $redirectResponse */

        return $redirectResponse;
    }
}
