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
use PHPUnit_Framework_TestCase as UnitTest;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Request\InstitutionParamConverter;
use Symfony\Component\HttpFoundation\ParameterBag;

class InstitutionParamConverterTest extends UnitTest
{
    /**
     * @var \Mockery\MockInterface
     */
    private $request;

    /**
     * @var \Mockery\MockInterface
     */
    private $paramConverterConfig;

    public function setUp()
    {
        $this->request = m::mock('Symfony\Component\HttpFoundation\Request');
        $this->paramConverterConfig = m::mock('Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter');
    }

    /**
     * @test
     * @group api-bundle
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function an_exception_is_thrown_when_the_parameter_is_missing()
    {
        $this->request->query = $this->mockQuery(false);

        $converter = new InstitutionParamConverter();
        $converter->apply($this->request, $this->paramConverterConfig);
    }

    /**
     * @test
     * @group api-bundle
     */
    public function an_institution_is_set_as_attribute()
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

    private function mockQuery($returnValue)
    {
        $query = m::mock('Symfony\Component\HttpFoundation\ParameterBag');
        $query
            ->shouldReceive('get')
            ->once()
            ->with('institution', false)
            ->andReturn($returnValue);

        return $query;
    }
}
