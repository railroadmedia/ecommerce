<?php

namespace Railroad\Ecommerce\Transformers;

use Doctrine\Common\Persistence\Proxy;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\TaxService;

class SubscriptionTransformer extends TransformerAbstract
{
    /**
     * @var TaxService
     */
    private $taxService;

    /**
     * @var CurrencyService
     */
    private $currencyService;

    /**
     * SubscriptionTransformer constructor.
     */
    public function __construct()
    {
        $this->taxService = app(TaxService::class);
        $this->currencyService = app(CurrencyService::class);
    }

    /**
     * @param Subscription $subscription
     *
     * @return array
     */
    public function transform(Subscription $subscription)
    {
        $this->defaultIncludes = [];

        if ($subscription->getProduct()) {
            // product relation is nullable
            $this->defaultIncludes[] = 'product';
        }

        if ($subscription->getUser()) {
            // user relation is nullable
            $this->defaultIncludes[] = 'user';
        }

        if ($subscription->getCustomer()) {
            // customer relation is nullable
            $this->defaultIncludes[] = 'customer';
        }

        if ($subscription->getOrder()) {
            // order relation is nullable
            $this->defaultIncludes[] = 'order';
        }

        if ($subscription->getPaymentMethod()) {
            // paymentMethod relation is nullable
            $this->defaultIncludes[] = 'paymentMethod';
        }

        if ($subscription->getFailedPayment()) {
            // paymentMethod relation is nullable
            $this->defaultIncludes[] = 'failedPayment';
        }

        /** @var $address Address */
        if (!empty($subscription->getPaymentMethod()) &&
            !empty($subscription->getPaymentMethod()->getBillingAddress())) {
            $address = $subscription->getPaymentMethod()->getBillingAddress()->toStructure();
        } else {
            $address = new Address();
        }

        $currency = $subscription->getCurrency();

        // all taxes for recurring payments are now calculated on the fly
        $subscriptionPricePerPayment = round($subscription->getTotalPrice(), 2);

        $taxes = $this->taxService->getTaxesDueTotal(
            $subscriptionPricePerPayment,
            0,
            $address
        );

        $chargePrice = $this->currencyService->convertFromBase(
            round($subscriptionPricePerPayment + $taxes, 2),
            $currency
        );

        return [
            'id' => $subscription->getId(),
            'brand' => $subscription->getBrand(),
            'type' => $subscription->getType(),
            'is_active' => $subscription->getIsActive(),
            'stopped' => $subscription->getStopped(),
            'state' => $subscription->getState(),
            'start_date' => $subscription->getStartDate() ?
                $subscription->getStartDate()
                    ->toDateTimeString() : null,
            'paid_until' => $subscription->getPaidUntil() ?
                $subscription->getPaidUntil()
                    ->toDateTimeString() : null,
            'canceled_on' => $subscription->getCanceledOn() ?
                $subscription->getCanceledOn()
                    ->toDateTimeString() : null,
            'cancellation_reason' => $subscription->getCancellationReason(),
            'note' => $subscription->getNote(),
            'total_price' => $subscription->getTotalPrice(),
            'tax' => $taxes,
            'currency' => $subscription->getCurrency(),
            'interval_type' => $subscription->getIntervalType(),
            'interval_count' => $subscription->getIntervalCount(),
            'total_cycles_due' => $subscription->getTotalCyclesDue(),
            'total_cycles_paid' => $subscription->getTotalCyclesPaid(),
            'renewal_attempt' => $subscription->getRenewalAttempt(),
            'deleted_at' => $subscription->getDeletedAt() ?
                $subscription->getDeletedAt()
                    ->toDateTimeString() : null,
            'created_at' => $subscription->getCreatedAt() ?
                $subscription->getCreatedAt()
                    ->toDateTimeString() : null,
            'updated_at' => $subscription->getUpdatedAt() ?
                $subscription->getUpdatedAt()
                    ->toDateTimeString() : null,
        ];
    }

    /**
     * @param Subscription $subscription
     *
     * @return Item
     */
    public function includeProduct(Subscription $subscription)
    {
        return $this->item(
            $subscription->getProduct(),
            new ProductTransformer(),
            'product'
        );
    }

    /**
     * @param Subscription $subscription
     *
     * @return Item
     */
    public function includeUser(Subscription $subscription)
    {
        $userProvider = app()->make(UserProviderInterface::class);

        $userTransformer = $userProvider->getUserTransformer();

        return $this->item(
            $subscription->getUser(),
            $userTransformer,
            'user'
        );
    }

    /**
     * @param Subscription $subscription
     *
     * @return Item
     */
    public function includeCustomer(Subscription $subscription)
    {
        if ($subscription->getCustomer() instanceof Proxy) {
            return $this->item(
                $subscription->getCustomer(),
                new EntityReferenceTransformer(),
                'customer'
            );
        } else {
            return $this->item(
                $subscription->getCustomer(),
                new CustomerTransformer(),
                'customer'
            );
        }
    }

    /**
     * @param Subscription $subscription
     *
     * @return Item
     */
    public function includeOrder(Subscription $subscription)
    {
        return $this->item(
            $subscription->getOrder(),
            new OrderTransformer(),
            'order'
        );
    }

    /**
     * @param Subscription $subscription
     *
     * @return Item
     */
    public function includePaymentMethod(Subscription $subscription)
    {
        return $this->item(
            $subscription->getPaymentMethod(),
            new PaymentMethodTransformer(),
            'paymentMethod'
        );
    }

    /**
     * @param Subscription $subscription
     *
     * @return Item
     */
    public function includeFailedPayment(Subscription $subscription)
    {
        if ($subscription->getFailedPayment() instanceof Proxy) {
            return $this->item(
                $subscription->getFailedPayment(),
                new EntityReferenceTransformer(),
                'payment'
            );
        } else {
            return $this->item(
                $subscription->getFailedPayment(),
                new PaymentTransformer(),
                'payment'
            );
        }
    }
}
