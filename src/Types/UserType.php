<?php

namespace Railroad\Ecommerce\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\IntegerType;
use Railroad\Ecommerce\Contracts\UserProviderInterface;

class UserType extends IntegerType
{
    const USER_TYPE = 'user';

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getUnsignedDeclaration($fieldDeclaration);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value !== null) {

            $userProvider = app()->make(UserProviderInterface::class);

            return $userProvider->getUserById($value);
        }

        return null;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value !== null) {

            $userProvider = app()->make(UserProviderInterface::class);

            return $userProvider->getUserId($value);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::USER_TYPE;
    }
}
