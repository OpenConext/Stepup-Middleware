<?php

declare(strict_types=1);

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
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;
use Surfnet\Stepup\Configuration\Value\Location;
use Surfnet\Stepup\Exception\InvalidArgumentException;
use TypeError;

/**
 * Custom Type for the Location Value Object for the Configuration domain
 */
class ConfigurationLocationType extends Type
{
    public const NAME = 'stepup_configuration_location';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getClobTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if (is_null($value)) {
            return null;
        }

        if (!$value instanceof Location) {
            throw new ConversionException(
                sprintf(
                    "Encountered illegal location of type %s '%s', expected a Location instance",
                    get_debug_type($value),
                    is_scalar($value) ? (string)$value : '',
                ),
            );
        }

        return $value->getLocation();
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Location
    {
        if (is_null($value)) {
            return null;
        }

        try {
            $location = new Location($value);
        } catch (InvalidArgumentException|TypeError $e) {
            throw ValueNotConvertible::new(
                $value,
                $this->getName(),
                $e->getMessage(),
                $e,
            );
        }

        return $location;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
