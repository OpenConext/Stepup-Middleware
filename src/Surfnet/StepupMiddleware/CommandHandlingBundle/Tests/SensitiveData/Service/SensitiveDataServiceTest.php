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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\SensitiveData\Service;

use Mockery as m;
use PHPUnit\Framework\TestCase as TestCase;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Service\SensitiveDataService;

final class SensitiveDataServiceTest extends TestCase
{
    /**
     * @test
     * @group sensitive-data
     */
    public function it_can_forget_sensitive_data_in_a_stream(): void
    {
        $identityId = new IdentityId('A');

        $sensitiveDataMessageStream = m::mock('Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\EventSourcing\SensitiveDataMessageStream');
        $sensitiveDataMessageStream->shouldReceive('forget')->once();
        $sensitiveDataMessageRepository = m::mock('Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Repository\SensitiveDataMessageRepository');
        $sensitiveDataMessageRepository->shouldReceive('findByIdentityId')->with($identityId)->once()->andReturn($sensitiveDataMessageStream);
        $sensitiveDataMessageRepository->shouldReceive('modify')->with($sensitiveDataMessageStream);

        $service = new SensitiveDataService($sensitiveDataMessageRepository);
        $service->forgetSensitiveData($identityId);

        $this->assertInstanceOf(SensitiveDataService::class, $service);
    }
}
