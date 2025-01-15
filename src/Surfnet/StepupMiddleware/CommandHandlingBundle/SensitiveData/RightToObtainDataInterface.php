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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData;

interface RightToObtainDataInterface
{
    /**
     * Obtains the user data, suitable for showing a user that exercised its
     * right to obtain user data.
     */
    public function obtainUserData(): array;

    /**
     * Retrieve the list of allowed data to retireve from the event.
     * Some data which is irrelevant for later reference is not included
     * on the allowlist. Some examples of data not on the allowlist are
     * a registration nonce, or a registration code.
     */
    public function getAllowlist(): array;
}
