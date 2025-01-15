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

namespace Surfnet\StepupMiddleware\ApiBundle\Service;

interface DeprovisionServiceInterface
{
    /**
     * Returns all data we know for the specified user. The
     * Identity events for the user are loaded and returned in
     * a portable format (JSON encodable arrays)
     */
    public function readUserData(string $collabPersonId): array;

    /**
     * The deprovision method applies the right to be forgotten
     * command on the pipeline. As this is a command, it does not
     * return any data. And as such, this method does not return
     * any user data either.
     */
    public function deprovision(string $collabPersonId): void;

    /**
     * This method checks if the right to be forgotten command
     * can be used on the specified user.
     */
    public function assertIsAllowed(string $collabPersonId): void;
}
