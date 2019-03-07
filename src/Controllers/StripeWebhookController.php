<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\CreditCard;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Services\ResponseService;

class StripeWebhookController extends BaseController
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * StripeWebhookController constructor.
     *
     * @param CreditCardRepository $creditCardRepository
     */
    public function __construct(
        EntityManager $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * Receive Stripe webhook notification with type 'customer.source.updated'.
     * The credit card data are updated with the webhook data send by Stripe.
     * Webhook data are sent as JSON in the POST request body.
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
            new NotFoundException(
                'Unexpected webhook type form Stripe!' . $data['type']
            )
        );

        $creditCards = $this->entityManager
                            ->getRepository(CreditCard::class)
                            ->findByExternalId($data['data']['object']['id']);

        $expirationDate = Carbon::createFromDate(
                $data['data']['object']['exp_year'],
                $data['data']['object']['exp_month']
            );

        foreach ($creditCards as $creditCard) {
            $creditCard
                ->setExpirationDate($expirationDate)
                ->setLastFourDigits($data['data']['object']['last4'])
                ->setUpdatedAt(Carbon::now());
        }

        $this->entityManager->flush();

        ResponseService::empty(200);
    }

}