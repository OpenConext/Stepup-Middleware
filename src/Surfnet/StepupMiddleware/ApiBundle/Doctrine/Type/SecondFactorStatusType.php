<?php

/**
 * Copyright 2014 SURFnet bv
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Surfnet\StepupMiddleware\ApiBundle\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\SecondFactorStatus;

/**
 * Custom Doctrine Type for the four possible statuses a second factor can be in: unverified, verified, vetted and
 * revoked.
 */
class SecondFactorStatusType extends Type
{
    public const NAME = 'stepup_second_factor_status';

    /**
     * @param array $column
     * @param AbstractPlatform $platform
     * @return string
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getIntegerTypeDeclarationSQL($column);
    }

    /**
     * @throws ConversionException
     */
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): int
    {
        if (!$value instanceof SecondFactorStatus) {
            throw new ConversionException(
                sprintf(
                    "Encountered illegal second factor status of type %s '%s', expected a SecondFactorStatus instance",
                    get_debug_type($value),
                    is_scalar($value) ? (string)$value : '',
                ),
            );
        }

        if (SecondFactorStatus::unverified()->equals($value)) {
            return 0;
        } elseif (SecondFactorStatus::verified()->equals($value)) {
            return 10;
        } elseif (SecondFactorStatus::vetted()->equals($value)) {
            return 20;
        } elseif (SecondFactorStatus::revoked()->equals($value)) {
            return 30;
        } elseif (SecondFactorStatus::forgotten()->equals($value)) {
            return 40;
        }

        throw new ConversionException(sprintf("Encountered inconvertible second factor status '%s'", (string)$value));
    }

    /**
     * @throws ConversionException
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): SecondFactorStatus
    {
        if (is_scalar($value)) {
            $value = (string)$value;
        }
        if ($value === '0') {
            return SecondFactorStatus::unverified();
        } elseif ($value === '10') {
            return SecondFactorStatus::verified();
        } elseif ($value === '20') {
            return SecondFactorStatus::vetted();
        } elseif ($value === '30') {
            return SecondFactorStatus::revoked();
        } elseif ($value === '40') {
            return SecondFactorStatus::forgotten();
        }

        throw new ConversionException(
            sprintf(
                "Encountered illegal second factor status of type %s '%s', expected it to be one of [0,10,20,30,40]",
                get_debug_type($value),
                is_scalar($value) ? $value : '',
            ),
        );
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return false;
    }
}
