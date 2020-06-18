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
    /**
     * @var IdentityId
     */
    private $identityId;

    /**
     * @var int
     */
    private $playhead;

    /**
     * @var SensitiveData
     */
    private $sensitiveData;

    /**
     * @param IdentityId $identityId
     * @param int $playhead The associated broadway domain message's playhead.
     * @param SensitiveData $sensitiveData
     */
    public function __construct(string $identityId, $playhead, SensitiveData $sensitiveData)
    {
        if (!is_int($playhead)) {
            throw InvalidArgumentException::invalidType('int', 'playhead', $playhead);
        }

        $this->identityId = new IdentityId($identityId);
        $this->playhead = $playhead;
        $this->sensitiveData = $sensitiveData;
    }

    /**
     * Forgets all contained sensitive data.
     */
    public function forget()
    {
        $this->sensitiveData = $this->sensitiveData->forget();
    }

    /**
     * @return IdentityId
     */
    public function getIdentityId()
    {
        return $this->identityId;
    }

    /**
     * @return int
     */
    public function getPlayhead()
    {
        return $this->playhead;
    }

    /**
     * @return SensitiveData
     */
    public function getSensitiveData()
    {
        return $this->sensitiveData;
    }
}
