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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Exception\BadApiRequestException;
use Surfnet\StepupMiddleware\ApiBundle\Request\InstitutionParamConverter;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class InstitutionParamConverterTest extends UnitTest
{
    use MockeryPHPUnitIntegration;

    private MockInterface&Request $request;

    private MockInterface&ParamConverter $paramConverterConfig;

    public function setUp(): void
    {
        $this->request = m::mock(Request::class);
        $this->paramConverterConfig = m::mock(ParamConverter::class);
    }

    /**
     * @test
     * @group api-bundle
     */
    public function an_exception_is_thrown_when_the_parameter_is_missing(): void
    {
        $this->expectException(BadApiRequestException::class);

        $this->request->query = $this->mockQuery(false);

        $converter = new InstitutionParamConverter();
        $converter->apply($this->request, $this->paramConverterConfig);
    }

    /**
     * @test
     * @group api-bundle
     */
    public function an_institution_is_set_as_attribute(): void
    {
        $query = $this->mockQuery('ABC');
        $query
            ->shouldReceive('remove')
            ->with('institution')
            ->once();

        $this->request->query = $query;
        $this->request->attributes = new ParameterBag();

        $equal = new Institution('ABC');

        $converter = new InstitutionParamConverter();
        $converter->apply($this->request, $this->paramConverterConfig);

        $this->assertTrue($this->request->attributes->get('institution')->equals($equal));
    }

    private function mockQuery(bool|string $returnValue): ParameterBag&MockInterface
    {
        $query = m::mock(ParameterBag::class);
        $query
            ->shouldReceive('get')
            ->once()
            ->with('institution', false)
            ->andReturn($returnValue);

        return $query;
    }
}
