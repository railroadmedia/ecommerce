<?php

namespace Railroad\Ecommerce\Transformers;

use Doctrine\Common\Persistence\Proxy;
use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\UserProduct;

class UserProductTransformer extends TransformerAbstract
{
    protected $defaultIncludes = [];

    public function transform(UserProduct $userProduct)
    {
        $this->defaultIncludes[] = 'user';
        $this->defaultIncludes[] = 'product';

        return [
            'id' => $userProduct->getId(),
            'quantity' => $userProduct->getQuantity(),
            'expiration_date' => !empty($userProduct->getExpirationDate()) ?
                $userProduct->getExpirationDate()
                    ->toDateTimeString() : null,
            'created_at' => $userProduct->getCreatedAt() ?
                $userProduct->getCreatedAt()
                    ->toDateTimeString() : null,
            'updated_at' => $userProduct->getUpdatedAt() ?
                $userProduct->getUpdatedAt()
                    ->toDateTimeString() : null,
        ];
    }

    public function includeUser(UserProduct $userProduct)
    {
        $userProvider = app()->make(UserProviderInterface::class);

        $userTransformer = $userProvider->getUserTransformer();

        return $this->item(
            $userProduct->getUser(),
            $userTransformer,
            'user'
        );
    }

    public function includeProduct(UserProduct $userProduct)
    {
        if ($userProduct->getProduct() instanceof Proxy) {
            return $this->item(
                $userProduct->getProduct(),
                new EntityReferenceTransformer(),
                'product'
            );
        }
        else {
            return $this->item(
                $userProduct->getProduct(),
                new ProductTransformer(),
                'product'
            );
        }
    }
}
