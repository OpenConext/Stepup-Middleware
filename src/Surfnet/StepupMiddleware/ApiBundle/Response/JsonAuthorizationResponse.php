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

namespace Surfnet\StepupMiddleware\ApiBundle\Response;

use Assert\Assertion;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\AuthorizationDecision;
use Symfony\Component\HttpFoundation\JsonResponse;

class JsonAuthorizationResponse extends JsonResponse
{
    public function __construct(int $code, array $errors = [])
    {
        Assertion::choice($code, [200, 403], 'The status code can be either 200 or 403');
        Assertion::allString($errors, 'The error messages should all be strings');

        $data = [
            'code' => $code
        ];
        if ($errors) {
            $data['errors'] = $errors;
        }
        // Don't confuse the HTTP status code with the authorization status code
        parent::__construct($data, 200);
    }

    public static function from(AuthorizationDecision $decision): self
    {
        return new self($decision->getCode(), $decision->getErrorMessages());
    }
}
