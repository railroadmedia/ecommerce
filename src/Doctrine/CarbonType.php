<?php

namespace Railroad\Ecommerce\Doctrine;

use Carbon\Carbon;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeType;

class CarbonType extends DateTimeType
{
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $value ? Carbon::instance(
                parent::convertToPHPValue($value, $platform)
            ) : null;
    }
}
