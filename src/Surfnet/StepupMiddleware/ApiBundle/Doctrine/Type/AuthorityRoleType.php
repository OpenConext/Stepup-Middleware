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
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;
use Surfnet\StepupMiddleware\ApiBundle\Exception\InvalidArgumentException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\AuthorityRole;

/**
 * Custom Type for the AuthorityRole Value Object
 */
class AuthorityRoleType extends Type
{
    public const NAME = 'authority_role';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if (!isset($column['length'])) {
            $column['length'] = 20;
        }

        return $platform->getStringTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (is_null($value)) {
            return null;
        }

        return (string)$value;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?AuthorityRole
    {
        if (is_null($value)) {
            return null;
        }

        try {
            $authorityRole = new AuthorityRole($value);
        } catch (InvalidArgumentException $e) {
            throw ValueNotConvertible::new(
                $value,
                $this->getName(),
                $e->getMessage(),
                $e,
            );
        }

        return $authorityRole;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
