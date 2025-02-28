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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\SensitiveData\EventSourcing;

use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Forgettable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\SensitiveData;

final class ForgettableEventStub implements Forgettable
{
    /**
     * @var SensitiveData
     */
    public SensitiveData $sensitiveData;

    /**
     * @return SensitiveData
     */
    public function getSensitiveData(): SensitiveData
    {
        return $this->sensitiveData;
    }

    /**
     * @param SensitiveData $sensitiveData
     * @return void
     */
    public function setSensitiveData(SensitiveData $sensitiveData): void
    {
        $this->sensitiveData = $sensitiveData;
    }
}
