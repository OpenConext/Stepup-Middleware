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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Request;

use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase as UnitTest;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Exception\BadApiRequestException;
use Surfnet\StepupMiddleware\ApiBundle\Request\InstitutionValueResolver;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class InstitutionValueResolverTest extends UnitTest
{
    use MockeryPHPUnitIntegration;

    private MockInterface&Request $request;

    private MockInterface&ArgumentMetadata $argument;

    public function setUp(): void
    {
        $this->request = m::mock(Request::class);
        $this->argument = m::mock(ArgumentMetadata::class);
        $this->argument->shouldReceive('getType')
            ->once()
            ->andReturn(Institution::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('api-bundle')]
    public function an_exception_is_thrown_when_the_parameter_is_missing(): void
    {
        $this->expectException(BadApiRequestException::class);

        $this->request->query = $this->mockQuery(false);

        $converter = new InstitutionValueResolver();
        $converter->resolve($this->request, $this->argument);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('api-bundle')]
    public function an_institution_is_resolved(): void
    {
        $query = $this->mockQuery('ABC');

        $this->request->query = $query;

        $equal = new Institution('ABC');

        $converter = new InstitutionValueResolver();
        $result = $converter->resolve($this->request, $this->argument);

        $this->assertCount(1, $result);
        $this->assertEquals($equal, $result[0]);
    }

    private function mockQuery(bool|string $returnValue): ParameterBag&MockInterface
    {
        $query = m::mock(ParameterBag::class);
        $query
            ->shouldReceive('get')
            ->once()
            ->with('institution')
            ->andReturn($returnValue);

        return $query;
    }
}
