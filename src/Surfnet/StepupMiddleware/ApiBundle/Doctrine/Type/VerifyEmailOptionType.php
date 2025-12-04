<?php

/**
 * Copyright 2018 SURFnet B.V.
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
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\ConversionException;
use Surfnet\Stepup\Configuration\Value\VerifyEmailOption;
use Surfnet\Stepup\Exception\InvalidArgumentException;

/**
 * Custom Type for the VerifyEmailOption Value Object
 */
class VerifyEmailOptionType extends BooleanType
{
    public const NAME = 'stepup_verify_email_option';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getBooleanTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?int
    {
        if (is_null($value)) {
            return null;
        }

        if (!$value instanceof VerifyEmailOption) {
            throw new ConversionException(
                sprintf(
                    "Encountered illegal location of type %s '%s', expected a VerifyEmailOption instance",
                    get_debug_type($value),
                    is_scalar($value) ? (string)$value : '',
                ),
            );
        }

        return (int)$value->isEnabled();
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?VerifyEmailOption
    {
        if (is_null($value)) {
            return null;
        }

        try {
            $verifyEmailOption = new VerifyEmailOption($platform->convertFromBoolean($value));
        } catch (InvalidArgumentException $e) {
            // get nice standard message, so we can throw it keeping the exception chain
            $doctrineExceptionMessage = ConversionException::conversionFailed(
                $value,
                $this->getName(),
            )->getMessage();

            throw new ConversionException($doctrineExceptionMessage, 0, $e);
        }

        return $verifyEmailOption;
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
