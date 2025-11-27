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

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;
use Surfnet\Stepup\Configuration\Value\SsoRegistrationBypassOption;
use TypeError;

/**
 * Custom Type for the SsoRegistrationBypassOption Value Object
 *
 * This option enables and disables the "GSSP fallback" option in the Stepup-Gateway for an institution.
 * "GSSP fallback" forwards the second factor authentications at LoA 1.5 to the fallback GSSP when a user does not have
 * any active tokens
 */
class SsoRegistrationBypassOptionType extends Type
{
    public const NAME = 'stepup_sso_registration_bypass_option';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getIntegerTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?int
    {
        if (is_null($value)) {
            return null;
        }

        if (!$value instanceof SsoRegistrationBypassOption) {
            throw new ConversionException(
                sprintf(
                    "Encountered illegal sso registration bypass option %s '%s', expected a 
                    SsoRegistrationBypassOption instance",
                    get_debug_type($value),
                    is_scalar($value) ? (string)$value : '',
                ),
            );
        }

        return (int)$value->isEnabled();
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?SsoRegistrationBypassOption
    {
        if (is_null($value)) {
            return null;
        }

        try {
            $ssoRegistrationBypassOption = new SsoRegistrationBypassOption((bool)$value);
        } catch (TypeError $e) {
            throw ValueNotConvertible::new(
                $value,
                $this->getName(),
                $e->getMessage(),
                $e,
            );
        }

        return $ssoRegistrationBypassOption;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getBindingType(): ParameterType
    {
        return ParameterType::INTEGER;
    }
}
