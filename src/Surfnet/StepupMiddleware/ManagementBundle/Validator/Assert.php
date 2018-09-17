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

namespace Surfnet\StepupMiddleware\ManagementBundle\Validator;

use Assert\InvalidArgumentException;

final class Assert
{
    public static function keysMatch(array $value, array $keys, $message = null, $propertyPath = null)
    {
        $keysOfValue = array_keys($value);
        $extraKeys = array_diff($keysOfValue, $keys);
        $missingKeys = array_diff($keys, $keysOfValue);

        if (count($extraKeys) === 0 && count($missingKeys) === 0) {
            return;
        }

        throw new InvalidArgumentException(
            $message,
            0,
            $propertyPath,
            $value,
            ['expected' => $keys, 'actual' => $keysOfValue]
        );
    }

    public static function requiredAndOptionalOptions(array $value, array $required, array $optional, $message = null, $propertyPath = null)
    {
        // Filter out the optional items from the value array
        $requiredValueSet = array_diff_key($value, array_flip($optional));

        // Verify the required keys match.
        self::keysMatch($requiredValueSet, $required, $message, $propertyPath);

        // Verify the optional keys do not contain illegal entries.
        $keysOfValue = array_keys($value);
        $extraKeys = array_diff($keysOfValue, array_merge($optional, $required));

        if (count($extraKeys) === 0) {
            return;
        }

        throw new InvalidArgumentException(
            $message,
            0,
            $propertyPath,
            $value,
            ['expected' => $optional, 'actual' => $keysOfValue]
        );
    }
}
