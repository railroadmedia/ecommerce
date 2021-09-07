<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Requests\OrderFormCreateIntentRequest;
use Railroad\Ecommerce\Requests\OrderFormSubmitRequest;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\OrderFormService;
use Railroad\Ecommerce\Services\ResponseService;
use Spatie\Fractal\Fractal;
use Stripe\Customer;
use Stripe\SetupIntent;
use Stripe\Stripe;
use Throwable;

class OrderFormJsonController extends Controller
{
    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var OrderFormService
     */
    private $orderFormService;

    /**
     * OrderFormJsonController constructor.
     *
     * @param CartService $cartService
     * @param OrderFormService $orderFormService
     */
    public function __construct(
        CartService $cartService,
        OrderFormService $orderFormService
    )
    {
        $this->cartService = $cartService;
        $this->orderFormService = $orderFormService;
    }

    /**
     * @return Fractal
     *
     * @throws Throwable
     */
    public function index()
    {
        $this->cartService->refreshCart();

        // if the cart it's empty; we throw an exception
        throw_if(
            empty(
            $this->cartService->getCart()
                ->getItems()
            ),
            new NotFoundException('The cart it\'s empty')
        );

        $cartArray = $this->cartService->toArray();

        return ResponseService::cart($cartArray);
    }

    /**
     * Submit an order
     *
     * @param OrderFormSubmitRequest $request
     *
     * @return JsonResponse|Fractal
     *
     * @throws Throwable
     */
    public function submitOrder(OrderFormSubmitRequest $request)
    {
        $this->cartService->refreshCart();

        // if the cart it's empty; we throw an exception
        throw_if(
            empty(
            $this->cartService->getCart()
                ->getItems()
            ),
            new NotFoundException('The cart is empty')
        );

        $result = $this->orderFormService->processOrderFormSubmit($request);

        if (isset($result['order'])) {
            if (!empty($result['order']->getCustomer())) {
                return ResponseService::order($result['order'])
                    ->addMeta(['redirect' => config('ecommerce.post_purchase_redirect_customer_order')]);
            } else {
                return ResponseService::order($result['order'])
                    ->addMeta(['redirect' => config('ecommerce.post_purchase_redirect_digital_items')]);
            }
        }
        elseif (isset($result['errors'])) {
            $errors = [];
            foreach ($result['errors'] as $message) {

                $errors[] = [
                    'title' => 'Payment failed.',
                    'detail' => $message,
                ];
            }

            return response()->json(
                [
                    'errors' => $errors,
                ],
                404
            );
        }
        elseif ($result['redirect'] && !isset($result['errors'])) {

            return ResponseService::redirect($result['redirect']);
        }
    }

    /**
     * Submit an order
     *
     * @param OrderFormSubmitRequest $request
     *
     * @return JsonResponse|Fractal
     *
     * @throws Throwable
     */
    public function createIntent(OrderFormCreateIntentRequest $request)
    {
        // todo: refactor
        $gatewayName = 'pianote';

        $config = config('ecommerce.payment_gateways')['stripe'][$gatewayName] ?? '';

        if (empty($config)) {
            throw new PaymentFailedException('Gateway ' . $gatewayName . ' is not configured.');
        }

        Stripe::setApiKey($config['stripe_api_secret']);

        $customer = Customer::create();

        $intent = SetupIntent::create(['customer' => $customer->id]);

        // $intent = SetupIntent::retrieve('seti_1JWzMRHhsuEXdZnAed7aYSrc', []);

        // initial state: status: "requires_payment_method"
        // new state: status: "succeeded"

        // dd($intent);

        // dd([
        //     'customer_id' => $customer->id,
        //     'intent_id' => $intent->id,
        //     'intent_client_secret' => $intent->client_secret,
        //     'customer' => $customer,
        //     'intent' => $intent,
        // ]);

        // return response()->json([
        //     'customer_id' => 'cus_KBM4pZqBx6QlxB',
        //     'intent_id' => 'seti_1JWzMRHhsuEXdZnAed7aYSrc',
        //     'intent_client_secret' => 'seti_1JWzMRHhsuEXdZnAed7aYSrc_secret_KBM4raFxBcxGXQTZtmk19WOI7ku7wwi'
        // ]);

        // create payment intent https://stripe.com/docs/api/payment_intents/confirm?lang=php
        // use SetupIntent payment_method: "pm_1JX4TnHhsuEXdZnAA20SKvwk"

        /*
        Stripe\SetupIntent {#2666 ▼
          +saveWithParent: false
          #_opts: Stripe\Util\RequestOptions {#2687 ▶}
          #_originalValues: array:21 [▶]
          #_values: array:21 [▶]
          #_unsavedValues: Stripe\Util\Set {#2672 ▶}
          #_transientValues: Stripe\Util\Set {#2670 ▶}
          #_retrieveOptions: []
          #_lastResponse: Stripe\ApiResponse {#2686 ▶}
          id: "seti_1JWzMRHhsuEXdZnAed7aYSrc"
          object: "setup_intent"
          application: null
          cancellation_reason: null
          client_secret: "seti_1JWzMRHhsuEXdZnAed7aYSrc_secret_KBM4raFxBcxGXQTZtmk19WOI7ku7wwi"
          created: 1631002135
          customer: "cus_KBM4pZqBx6QlxB"
          description: null
          last_setup_error: null
          latest_attempt: "setatt_1JX4TnHhsuEXdZnAHkgVOR3R"
          livemode: false
          mandate: null
          metadata: Stripe\StripeObject {#2689 ▶}
          next_action: null
          on_behalf_of: null
          payment_method: "pm_1JX4TnHhsuEXdZnAA20SKvwk"
          payment_method_options: Stripe\StripeObject {#2690 ▶}
          payment_method_types: array:1 [▶]
          single_use_mandate: null
          status: "succeeded"
          usage: "off_session"
        }
        */

        return response()->json([
            'intent_client_secret' => $intent->client_secret,
        ]);
    }
}
