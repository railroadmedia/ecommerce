<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repositories\CreditCardRepository;

class StripeWebhookController extends BaseController
{
    /**
     * @var CreditCardRepository
     */
    private $creditCardRepository;

    /**
     * StripeWebhookController constructor.
     *
     * @param CreditCardRepository $creditCardRepository
     */
    public function __construct(CreditCardRepository $creditCardRepository)
    {
        $this->creditCardRepository = $creditCardRepository;
    }

    /** Receive Stripe webhook notification with type 'customer.source.updated'.
     *  The credit card data are updated with the webhook data send by Stripe.
     *  Webhook data are sent as JSON in the POST request body.
     *
     * @param Request $request
     * @return mixed
     */
    public function handleCustomerSourceUpdated(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        throw_if(
            is_null($data),
            new NotFoundException('Bad JSON body from Stripe!')
        );

        throw_if(
            ($data['type'] != 'customer.source.updated'),
            new NotFoundException('Unexpected webhook type form Stripe!' . $data['type'])
        );

        $creditCardIds =
            $this->creditCardRepository->query()
                ->where(
                    [
                        'external_id' => $data['data']['object']['id'],
                    ]
                )
                ->get();

        $expirationDate = Carbon::createFromDate(
            $data['data']['object']['exp_year'],
            $data['data']['object']['exp_month']
        )
            ->toDateTimeString();

        foreach ($creditCardIds as $creditCardId) {
            $this->creditCardRepository->update(
                $creditCardId['id'],
                [
                    'expiration_date' => $expirationDate,
                    'last_four_digits' => $data['data']['object']['last4'],
                    'updated_on' => Carbon::now()
                        ->toDateTimeString(),
                ]
            );
        }

        return reply()->json(
            null,
            [
                'code' => 200,
            ]
        );
    }

}