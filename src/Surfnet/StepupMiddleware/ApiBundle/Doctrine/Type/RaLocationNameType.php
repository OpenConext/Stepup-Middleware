<?php

/**
 * Copyright 2016 SURFnet B.V.
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
use Surfnet\Stepup\Configuration\Value\RaLocationName;
use Surfnet\Stepup\Exception\InvalidArgumentException;

/**
 * Custom Type for the RaLocationName Value Object
 */
class RaLocationNameType extends Type
{
    public const NAME = 'stepup_ra_location_name';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getVarcharTypeDeclarationSQL([]);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (is_null($value)) {
            return $value;
        }

        if (!$value instanceof RaLocationName) {
            throw new ConversionException(
                sprintf(
                    "Encountered illegal RA location name of type %s '%s', expected a RaLocationName instance",
                    get_debug_type($value),
                    is_scalar($value) ? (string)$value : '',
                ),
            );
        }

        return $value->getRaLocationName();
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?RaLocationName
    {
        if (is_null($value)) {
            return $value;
        }

        try {
            $raLocationName = new RaLocationName($value);
        } catch (InvalidArgumentException $e) {
            // get nice standard message, so we can throw it keeping the exception chain
            $doctrineExceptionMessage = ConversionException::conversionFailed(
                $value,
                $this->getName(),
            )->getMessage();

            throw new ConversionException($doctrineExceptionMessage, 0, $e);
        }

        return $raLocationName;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
