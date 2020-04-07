<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\User;

class UserTransformer extends TransformerAbstract
{
    /**
     * @param User $user
     *
     * @return array
     */
    public function transform(User $user)
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
        ];
    }
}
