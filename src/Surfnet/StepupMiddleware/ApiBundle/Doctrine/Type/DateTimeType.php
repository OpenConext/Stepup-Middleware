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
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Surfnet\Stepup\DateTime\DateTime;

/**
 * Custom Type for the Surfnet\Stepup\DateTime\DateTime Object
 */
class DateTimeType extends Type
{
    public const NAME = 'stepup_datetime';

    /**
     * @param array $fieldDeclaration
     * @param AbstractPlatform $platform
     * @return string
     * @throws DBALException
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getDateTimeTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * @param mixed $value
     * @param AbstractPlatform $platform
     * @return null|string
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        // Note that we have no guarantee here that UTC is coming in.
        // See: https://www.pivotaltracker.com/projects/1163646/stories/121758429

        return $value->format($platform->getDateTimeFormatString());
    }

    /**
     * @param mixed $value
     * @param AbstractPlatform $platform
     * @return null|DateTime
     * @throws ConversionException
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (is_null($value)) {
            return $value;
        }

        $dateTime = CoreDateTime::createFromFormat(
            $platform->getDateTimeFormatString(),
            $value,
            new DateTimeZone('UTC'),
        );

        if (!$dateTime) {
            throw ConversionException::conversionFailedFormat(
                $value,
                $this->getName(),
                $platform->getDateTimeFormatString(),
            );
        }

        return new DateTime($dateTime);
    }

    public function getName()
    {
        return self::NAME;
    }
}
