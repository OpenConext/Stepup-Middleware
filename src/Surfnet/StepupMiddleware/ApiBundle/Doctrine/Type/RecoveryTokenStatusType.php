<?php

/**
 * Copyright 2022 SURFnet bv
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
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RecoveryTokenStatus;

/**
 * Custom Doctrine Type for the four possible statuses a recovery token
 */
class RecoveryTokenStatusType extends Type
{
    public const NAME = 'stepup_recovery_token_status';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getIntegerTypeDeclarationSQL($column);
    }

    /**
     * @throws ConversionException
     */
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): int
    {
        if (!$value instanceof RecoveryTokenStatus) {
            throw new ConversionException(
                sprintf(
                    "Encountered illegal recovery token status of type %s '%s', expected a RecoveryTokenStatus instance",
                    get_debug_type($value),
                    is_scalar($value) ? (string)$value : '',
                ),
            );
        }

        if (RecoveryTokenStatus::active()->equals($value)) {
            return 0;
        } elseif (RecoveryTokenStatus::revoked()->equals($value)) {
            return 10;
        } elseif (RecoveryTokenStatus::forgotten()->equals($value)) {
            return 20;
        }

        throw new ConversionException(sprintf("Encountered inconvertible second factor status '%s'", (string)$value));
    }

    /**
     * @throws ConversionException
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): RecoveryTokenStatus
    {
        if ($value === '0') {
            return RecoveryTokenStatus::active();
        } elseif ($value === '10') {
            return RecoveryTokenStatus::revoked();
        } elseif ($value === '20') {
            return RecoveryTokenStatus::forgotten();
        }

        throw new ConversionException(
            sprintf(
                "Encountered illegal recovery token status of type %s '%s', expected it to be one of [0,10,20]",
                get_debug_type($value),
                is_scalar($value) ? (string)$value : '',
            ),
        );
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
