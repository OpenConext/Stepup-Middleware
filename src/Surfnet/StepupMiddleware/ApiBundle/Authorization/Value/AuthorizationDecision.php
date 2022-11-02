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

namespace Surfnet\StepupMiddleware\ApiBundle\Authorization\Value;

use Assert\Assertion;

final class AuthorizationDecision
{
    private $code;

    private $errorMessages;

    public static function allowed()
    {
        return new self(200);
    }

    public static function denied(array $messages = [])
    {
        Assertion::allString($messages, 'The error messages should all be strings');
        return new self(403, $messages);
    }

    private function __construct(int $code, array $errorMessages = [])
    {
        $this->code = $code;
        $this->errorMessages = $errorMessages;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getErrorMessages(): array
    {
        return $this->errorMessages;
    }
}
