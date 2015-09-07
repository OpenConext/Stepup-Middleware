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

namespace Surfnet\Stepup\Identity\Value;

use Surfnet\Stepup\Exception\LogicException;
use Surfnet\StepupBundle\Value\SecondFactorType;

final class SecondFactorIdentifierFactory
{
    /**
     * @param SecondFactorType $type
     * @param string           $secondFactorIdentifier
     * @return SecondFactorIdentifier
     */
    public static function forType(SecondFactorType $type, $secondFactorIdentifier)
    {
        if ($type->isSms()) {
            return new PhoneNumber($secondFactorIdentifier);
        }

        if ($type->isYubikey()) {
            return new YubikeyPublicId($secondFactorIdentifier);
        }

        if ($type->isGssf()) {
            return new GssfId($secondFactorIdentifier);
        }

        if ($type->isU2f()) {
            return new U2fKeyHandle($secondFactorIdentifier);
        }

        throw new LogicException(sprintf('Unknown second factor type "%s" encountered', $type->getSecondFactorType()));
    }

    /**
     * @param SecondFactorType $type
     * @return SecondFactorIdentifier
     */
    public static function unknownForType(SecondFactorType $type)
    {
        if ($type->isSms()) {
            return PhoneNumber::unknown();
        }

        if ($type->isYubikey()) {
            return YubikeyPublicId::unknown();
        }

        if ($type->isGssf()) {
            return GssfId::unknown();
        }

        if ($type->isU2f()) {
            return U2fKeyHandle::unknown();
        }

        throw new LogicException(sprintf('Unknown second factor type "%s" encountered', $type->getSecondFactorType()));
    }
}
