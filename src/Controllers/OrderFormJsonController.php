<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
use Stripe\PaymentIntent;
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

        // Stripe::setApiKey($config['stripe_api_secret']);

        // $customer = Customer::create();

        // $intent = SetupIntent::create(['customer' => $customer->id]);

        // return response()->json([
        //     'intent_client_secret' => $intent->client_secret,
        // ]);

        return response()->json([
            'intent_client_secret' => 'seti_1JXoT3HhsuEXdZnAYvbtq5Sg_secret_KCCsPznfWQB3gLUMLVpNpKn3HdkVLzY',
        ]);
    }

    public function createIntentPayment(Request $request)
    {
        $gatewayName = 'pianote';

        $config = config('ecommerce.payment_gateways')['stripe'][$gatewayName] ?? '';

        if (empty($config)) {
            throw new PaymentFailedException('Gateway ' . $gatewayName . ' is not configured.');
        }

        Stripe::setApiKey($config['stripe_api_secret']);

        $intent = SetupIntent::retrieve('seti_1JXoT3HhsuEXdZnAYvbtq5Sg', []);

        $paymentIntent = PaymentIntent::create([
            'amount' => 8700,
            'confirm' => true,
            'currency' => 'usd',
            'customer' => $intent->customer,
            'payment_method' => $intent->payment_method
        ]);

        // $paymentIntent = PaymentIntent::retrieve('pi_3JXoUzHhsuEXdZnA0u4VxtrY');

        // dd($paymentIntent);

        // $customer = Customer::create();

        // $intent = SetupIntent::create(['customer' => $customer->id]);

        // dd($intent);

        return response()->json([
            'payment_intent_id' => $paymentIntent->id,
            'payment_intent_client_secret' => $paymentIntent->client_secret,
        ]);
    }
}
