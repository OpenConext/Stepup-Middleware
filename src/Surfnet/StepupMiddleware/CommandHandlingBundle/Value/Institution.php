<?php

/**
 * Copyright 2016 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Value;

use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\InvalidArgumentException;

final class Institution
{
    /**
     * @var string
     */
    private $institution;

    /**
     * @param string $institution may not be an empty string
     */
    public function __construct($institution)
    {
        if (!is_string($institution) || strlen(trim($institution)) === 0) {
            throw InvalidArgumentException::invalidType('non-empty string', 'institution', $institution);
        }

        $this->institution = trim($institution);
    }

    /**
     * @return string
     */
    public function getInstitution()
    {
        return $this->institution;
    }

    /**
     * @param Institution $otherInstitution
     * @return bool
     */
    public function equals(Institution $otherInstitution)
    {
        return $this->institution === $otherInstitution->institution;
    }

    public function __toString()
    {
        return $this->institution;
    }
}
