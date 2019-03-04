<?php

namespace Railroad\Ecommerce\Tests\Fixtures;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Contracts\UserInterface;

class UserTransformer extends TransformerAbstract
{
    public function transform(UserInterface $user)
    {
        return [
            'id' => $user->getId()
        ];
    }
}
