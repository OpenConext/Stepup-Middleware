<?php

declare(strict_types=1);

/**
 * Copyright 2016 SURFnet bv
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
use Surfnet\Stepup\Configuration\Value\ContactInformation;
use Surfnet\Stepup\Exception\InvalidArgumentException;
use TypeError;

/**
 * Custom Type for the ContactInformation Value Object for the Configuration domain
 */
class ConfigurationContactInformationType extends Type
{
    public const NAME = 'stepup_configuration_contact_information';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getClobTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if (is_null($value)) {
            return null;
        }

        if (!$value instanceof ContactInformation) {
            throw new ConversionException(
                sprintf(
                    "Encountered illegal contact information of type %s '%s', expected a ContactInformation instance",
                    get_debug_type($value),
                    is_scalar($value) ? (string)$value : '',
                ),
            );
        }

        return $value->getContactInformation();
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?ContactInformation
    {
        if (is_null($value)) {
            return null;
        }

        try {
            $contactInformation = new ContactInformation($value);
        } catch (InvalidArgumentException|TypeError $e) {
            // get nice standard message, so we can throw it keeping the exception chain
            $doctrineExceptionMessage = ConversionException::conversionFailed(
                $value,
                $this->getName(),
            )->getMessage();

            throw new ConversionException($doctrineExceptionMessage, 0, $e);
        }

        return $contactInformation;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
