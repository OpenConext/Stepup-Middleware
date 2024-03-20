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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\EventSourcing;

use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\InvalidArgumentException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\SensitiveData;

class SensitiveDataMessage
{
    private readonly IdentityId $identityId;

    private readonly int $playhead;

    public function __construct(IdentityId $identityId, int $playhead, private SensitiveData $sensitiveData)
    {
        if (!is_int($playhead)) {
            throw InvalidArgumentException::invalidType('int', 'playhead', $playhead);
        }

        $this->identityId = new IdentityId($identityId);
        $this->playhead = $playhead;
    }

    /**
     * Forgets all contained sensitive data.
     */
    public function forget(): void
    {
        $this->sensitiveData = $this->sensitiveData->forget();
    }

    /**
     * @return IdentityId
     */
    public function getIdentityId(): IdentityId
    {
        return $this->identityId;
    }

    /**
     * @return int
     */
    public function getPlayhead(): int
    {
        return $this->playhead;
    }

    /**
     * @return SensitiveData
     */
    public function getSensitiveData(): SensitiveData
    {
        return $this->sensitiveData;
    }
}
