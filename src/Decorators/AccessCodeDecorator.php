<?php

namespace Railroad\Ecommerce\Decorators;

use Railroad\Resora\Decorators\DecoratorInterface;

class AccessCodeDecorator implements DecoratorInterface
{
    public function decorate($accessCodes)
    {
        foreach ($accessCodes as $accessCodeIndex => $accessCode) {
            if ($accessCode['product_ids']) {
                $accessCodes[$accessCodeIndex]['product_ids'] =
                    unserialize($accessCode['product_ids']);
            }
        }

        return $accessCodes;
    }
}
