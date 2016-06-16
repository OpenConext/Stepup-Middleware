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

use DateTime as CoreDateTime;
use DateTimeZone;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\StepupMiddleware\ApiBundle\Exception\RuntimeException;

/**
 * Custom Type for the Surfnet\Stepup\DateTime\DateTime Object
 */
class UtcDateTimeType extends Type
{
    const NAME = 'stepup_utc_datetime';

    /**
     * @param array            $fieldDeclaration
     * @param AbstractPlatform $platform
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getDateTimeTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * @param mixed            $value
     * @param AbstractPlatform $platform
     * @return null|string
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof DateTime) {
            throw new RuntimeException('Value given is not a Stepup DateTime');
        }

        return $value->format($platform->getDateTimeFormatString());
    }

    /**
     * @param mixed            $value
     * @param AbstractPlatform $platform
     * @return null|DateTime
     * @throws ConversionException
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (is_null($value)) {
            return $value;
        }

        $dateTime = CoreDateTime::createFromFormat($platform->getDateTimeFormatString(), $value, new DateTimeZone('UTC'));

        if (!$dateTime) {
            throw ConversionException::conversionFailedFormat(
                $value,
                $this->getName(),
                $platform->getDateTimeFormatString()
            );
        }

        return new DateTime($dateTime);
    }

    public function getName()
    {
        return self::NAME;
    }
}
