<?php

/**
 * Copyright 2022 SURFnet B.V.
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
use Doctrine\DBAL\Types\IntegerType;
use Surfnet\Stepup\Configuration\Value\SelfAssertedTokensOption;
use TypeError;

/**
 * Custom Type for the SelfAssertedTokens options Value Object
 */
class SelfAssertedTokensOptionType extends IntegerType
{
    public const NAME = 'stepup_self_asserted_tokens_option';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getIntegerTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?int
    {
        if (is_null($value)) {
            return null;
        }

        if (!$value instanceof SelfAssertedTokensOption) {
            throw new ConversionException(
                sprintf(
                    "Encountered illegal self vet option %s '%s', expected a 
                    SelfAssertedTokensOption instance",
                    get_debug_type($value),
                    is_scalar($value) ? (string)$value : '',
                ),
            );
        }

        return (int)$value->isEnabled();
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?SelfAssertedTokensOption
    {
        if (is_null($value)) {
            return null;
        }

        try {
            $selfAssertedTokensOption = new SelfAssertedTokensOption((bool)$value);
        } catch (TypeError $e) {
            // get nice standard message, so we can throw it keeping the exception chain
            $doctrineExceptionMessage = ConversionException::conversionFailed(
                $value,
                $this->getName(),
            )->getMessage();

            throw new ConversionException($doctrineExceptionMessage, 0, $e);
        }

        return $selfAssertedTokensOption;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
