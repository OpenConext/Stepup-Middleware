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
use Surfnet\Stepup\Exception\InvalidArgumentException;
use Surfnet\StepupBundle\Value\SecondFactorType;

/**
 * Custom Type for the SecondFactorType Value Object
 */
class SecondFactorTypeType extends Type
{
    public const NAME = 'stepup_second_factor_type';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['length'] = $column['length'] ?? 255;
        return $platform->getStringTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (is_null($value)) {
            return null;
        }

        return (string)$value;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?SecondFactorType
    {
        if (is_null($value)) {
            return null;
        }

        try {
            $secondFactorType = new SecondFactorType($value);
        } catch (InvalidArgumentException $e) {
            throw ValueNotConvertible::new(
                $value,
                $this->getName(),
                $e->getMessage(),
                $e,
            );
        }

        return $secondFactorType;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
