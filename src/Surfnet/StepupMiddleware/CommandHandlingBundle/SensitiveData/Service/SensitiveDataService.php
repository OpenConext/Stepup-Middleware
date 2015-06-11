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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Service;

use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Repository\SensitiveDataMessageRepository;

class SensitiveDataService
{
    /**
     * @var \Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Repository\SensitiveDataMessageRepository
     */
    private $sensitiveDataMessageRepository;

    public function __construct(SensitiveDataMessageRepository $sensitiveDataMessageRepository)
    {
        $this->sensitiveDataMessageRepository = $sensitiveDataMessageRepository;
    }

    public function forgetSensitiveData(IdentityId $identityId)
    {
        $sensitiveDataMessageStream = $this->sensitiveDataMessageRepository->findByIdentityId($identityId);
        $sensitiveDataMessageStream->forget();

        $this->sensitiveDataMessageRepository->modify($sensitiveDataMessageStream);
    }
}
