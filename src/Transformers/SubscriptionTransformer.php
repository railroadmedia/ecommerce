<?php

namespace Railroad\Ecommerce\Transformers;

use Doctrine\Common\Persistence\Proxy;
use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Usora\Transformers\UserTransformer;

class SubscriptionTransformer extends TransformerAbstract
{
    public function transform(Subscription $subscription)
    {
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

        return [
            'id' => $subscription->getId(),
            'brand' => $subscription->getBrand(),
            'type' => $subscription->getType(),
            'is_active' => $subscription->getIsActive(),
            'start_date' => $subscription->getStartDate() ? $subscription->getStartDate()->toDateTimeString() : null,
            'paid_until' => $subscription->getPaidUntil() ? $subscription->getPaidUntil()->toDateTimeString() : null,
            'canceled_on' => $subscription->getCanceledOn() ? $subscription->getCanceledOn()->toDateTimeString() : null,
            'note' => $subscription->getNote(),
            'total_price_per_payment'=> $subscription->getTotalPricePerPayment(),
            'tax_per_payment'=> $subscription->getTaxPerPayment(),
            'shipping_per_payment'=> $subscription->getShippingPerPayment(),
            'currency'=> $subscription->getCurrency(),
            'interval_type'=> $subscription->getIntervalType(),
            'interval_count'=> $subscription->getIntervalCount(),
            'total_cycles_due'=> $subscription->getTotalCyclesDue(),
            'total_cycles_paid'=> $subscription->getTotalCyclesPaid(),
            'deleted_on'=> $subscription->getDeletedOn() ? $subscription->getDeletedOn()->toDateTimeString() : null,
            'created_at' => $subscription->getCreatedAt() ? $subscription->getCreatedAt()->toDateTimeString() : null,
            'updated_at' => $subscription->getUpdatedAt() ? $subscription->getUpdatedAt()->toDateTimeString() : null,
        ];
    }

    public function includeProduct(Subscription $subscription)
    {
        if ($subscription->getProduct() instanceof Proxy) {
            return $this->item(
                $subscription->getProduct(),
                new EntityReferenceTransformer(),
                'product'
            );
        } else {
            return $this->item(
                $subscription->getProduct(),
                new ProductTransformer(),
                'product'
            );
        }
    }

    public function includeUser(Subscription $subscription)
    {
        if ($subscription->getUser() instanceof Proxy) {
            return $this->item(
                $subscription->getUser(),
                new EntityReferenceTransformer(),
                'user'
            );
        } else {
            return $this->item(
                $subscription->getUser(),
                new UserTransformer(),
                'user'
            );
        }
    }

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

    public function includeOrder(Subscription $subscription)
    {
        if ($subscription->getOrder() instanceof Proxy) {
            return $this->item(
                $subscription->getOrder(),
                new EntityReferenceTransformer(),
                'order'
            );
        } else {
            return $this->item(
                $subscription->getOrder(),
                new OrderTransformer(),
                'order'
            );
        }
    }

    public function includePaymentMethod(Subscription $subscription)
    {
        if ($subscription->getPaymentMethod() instanceof Proxy) {
            return $this->item(
                $subscription->getPaymentMethod(),
                new EntityReferenceTransformer(),
                'paymentMethod'
            );
        } else {
            return $this->item(
                $subscription->getPaymentMethod(),
                new PaymentMethodTransformer(),
                'paymentMethod'
            );
        }
    }
}