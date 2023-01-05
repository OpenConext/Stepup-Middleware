<?php

/**
 * Copyright 2022 SURFnet bv
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

use Surfnet\Stepup\Exception\InvalidArgumentException;

final class RecoveryTokenIdentifierFactory
{
    public static function forType(RecoveryTokenType $type, string $recoveryTokenIdentifier): RecoveryTokenIdentifier
    {
        if ($type->isSms()) {
            return new PhoneNumber($recoveryTokenIdentifier);
        }
        if ($type->isSafeStore()) {
            return new SafeStore(new HashedSecret($recoveryTokenIdentifier));
        }
        throw new InvalidArgumentException(sprintf('Unsupported type given while building recovery method: "%s"', $type));
    }

    public static function unknownForType(RecoveryTokenType $type): RecoveryTokenIdentifier
    {
        if ($type->isSms()) {
            return PhoneNumber::unknown();
        }
        if ($type->isSafeStore()) {
            return SafeStore::unknown();
        }
        throw new InvalidArgumentException(
            sprintf('Unsupported type given while building unknown recovery method: "%s"', $type)
        );
    }
}
