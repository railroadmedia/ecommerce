<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Entities\CreditCard;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\CreditCardRepository;
use Railroad\Ecommerce\Services\ResponseService;
use Throwable;

class StripeWebhookController extends Controller
{
    /**
     * @var CreditCardRepository
     */
    private $creditCardRepository;

    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * StripeWebhookController constructor.
     *
     * @param CreditCardRepository $creditCardRepository
     * @param EcommerceEntityManager $entityManager
     */
    public function __construct(
        CreditCardRepository $creditCardRepository,
        EcommerceEntityManager $entityManager
    )
    {
        $this->creditCardRepository = $creditCardRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Receive Stripe webhook notification with type 'customer.source.updated'.
     * The credit card data are updated with the webhook data send by Stripe.
     * Webhook data are sent as JSON in the POST request body.
     *
     * @param Request $request
     *
     * @return mixed
     *
     * @throws Throwable
     */
    public function handleCustomerSourceUpdated(Request $request)
    {
        error_log($request->getContent()); // todo - to be removed
        $data = json_decode($request->getContent(), true);

        throw_if(
            is_null($data),
            new NotFoundException('Bad JSON body from Stripe!')
        );

        // docs about event: https://stripe.com/docs/api/events/types#event_types-customer.source.updated
        throw_if(
            ($data['type'] != 'customer.source.updated'),
            new NotFoundException(
                'Unexpected webhook type form Stripe!' . $data['type']
            )
        );

        // data.object is a source (e.g., card)
        // docs about card object: https://stripe.com/docs/api/cards/object
        $creditCards = $this->creditCardRepository->findByExternalId($data['data']['object']['id']);

        $expirationDate = Carbon::createFromDate(
            $data['data']['object']['exp_year'],
            $data['data']['object']['exp_month']
        );

        foreach ($creditCards as $creditCard) {
            /**
             * @var $creditCard CreditCard
             */
            $creditCard->setExpirationDate($expirationDate);
            $creditCard->setLastFourDigits($data['data']['object']['last4']);
            $creditCard->setUpdatedAt(Carbon::now());
        }

        $this->entityManager->flush();

        return ResponseService::empty(200);
    }

}