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
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\Stepup\Exception\InvalidArgumentException;

/**
 * Custom Type for the InstitutionRole Value Object
 */
class InstitutionRoleType extends Type
{
    public const NAME = 'stepup_institution_role';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['length'] = $column['length'] ?? 255;
        return $platform->getStringTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if (is_null($value)) {
            return null;
        }

        if (!$value instanceof InstitutionRole) {
            throw new ConversionException(
                sprintf(
                    "Encountered illegal location of type %s '%s', expected a InstitutionRole instance",
                    get_debug_type($value),
                    is_scalar($value) ? (string)$value : '',
                ),
            );
        }

        return $value->getType();
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?InstitutionRole
    {
        if (is_null($value)) {
            return null;
        }

        try {
            $institutionRole = new InstitutionRole($value);
        } catch (InvalidArgumentException $e) {
            throw ValueNotConvertible::new(
                $value,
                $this->getName(),
                $e->getMessage(),
                $e,
            );
        }

        return $institutionRole;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
