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
use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Command\ForgetSensitiveDataCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Service\SensitiveDataService;

final class SensitiveDataServiceTest extends TestCase
{
    /**
     * @test
     * @group sensitive-data
     */
    public function it_can_forget_sensitive_data_in_a_stream()
    {
        $command = new ForgetSensitiveDataCommand();
        $command->nameId = 'Martin Freeman';
        $command->institution = 'SURFnetbv';

        $identityRepository = m::mock('Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository');
        $identityRepository
            ->shouldReceive('findOneByNameIdAndInstitution')
            ->once()
            ->with(m::anyOf(new NameId($command->nameId)), m::anyOf(new Institution($command->institution)))
            ->andReturn((object) ['id' => 'A']);

        $sensitiveDataMessageStream = m::mock('Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\EventSourcing\SensitiveDataMessageStream');
        $sensitiveDataMessageStream->shouldReceive('forget')->once();
        $sensitiveDataMessageRepository = m::mock('Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Repository\SensitiveDataMessageRepository');
        $sensitiveDataMessageRepository->shouldReceive('findByIdentityId')->with(m::anyOf(new IdentityId('A')))->once()->andReturn($sensitiveDataMessageStream);
        $sensitiveDataMessageRepository->shouldReceive('update')->with($sensitiveDataMessageStream);

        $service = new SensitiveDataService($sensitiveDataMessageRepository, $identityRepository);
        $service->forgetSensitiveData($command);
    }
}
