<?php

namespace Railroad\Ecommerce\Transformers;

use Doctrine\Common\Persistence\Proxy;
use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\Subscription;

class SubscriptionTransformer extends TransformerAbstract
{
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

        return [
            'id' => $subscription->getId(),
            'brand' => $subscription->getBrand(),
            'type' => $subscription->getType(),
            'is_active' => $subscription->getIsActive(),
            'start_date' => $subscription->getStartDate() ?
                $subscription->getStartDate()
                    ->toDateTimeString() : null,
            'paid_until' => $subscription->getPaidUntil() ?
                $subscription->getPaidUntil()
                    ->toDateTimeString() : null,
            'canceled_on' => $subscription->getCanceledOn() ?
                $subscription->getCanceledOn()
                    ->toDateTimeString() : null,
            'note' => $subscription->getNote(),
            'total_price' => $subscription->getTotalPrice(),
            'tax' => $subscription->getTax(),
            'currency' => $subscription->getCurrency(),
            'interval_type' => $subscription->getIntervalType(),
            'interval_count' => $subscription->getIntervalCount(),
            'total_cycles_due' => $subscription->getTotalCyclesDue(),
            'total_cycles_paid' => $subscription->getTotalCyclesPaid(),
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

    public function includeProduct(Subscription $subscription)
    {
        return $this->item(
            $subscription->getProduct(),
            new ProductTransformer(),
            'product'
        );
    }

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

    public function includeCustomer(Subscription $subscription)
    {
        if ($subscription->getCustomer() instanceof Proxy) {
            return $this->item(
                $subscription->getCustomer(),
                new EntityReferenceTransformer(),
                'customer'
            );
        }
        else {
            return $this->item(
                $subscription->getCustomer(),
                new CustomerTransformer(),
                'customer'
            );
        }
    }

    public function includeOrder(Subscription $subscription)
    {
        return $this->item(
            $subscription->getOrder(),
            new OrderTransformer(),
            'order'
        );
    }

    public function includePaymentMethod(Subscription $subscription)
    {
        return $this->item(
            $subscription->getPaymentMethod(),
            new PaymentMethodTransformer(),
            'paymentMethod'
        );
    }

    public function includeFailedPayment(Subscription $subscription)
    {
        if ($subscription->getFailedPayment() instanceof Proxy) {
            return $this->item(
                $subscription->getFailedPayment(),
                new EntityReferenceTransformer(),
                'payment'
            );
        }
        else {
            return $this->item(
                $subscription->getFailedPayment(),
                new PaymentTransformer(),
                'payment'
            );
        }
    }
}
