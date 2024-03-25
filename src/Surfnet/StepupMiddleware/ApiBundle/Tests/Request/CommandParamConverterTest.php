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
use Mockery\Matcher\MatcherAbstract;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use stdClass;
use Surfnet\StepupMiddleware\ApiBundle\Exception\BadCommandRequestException;
use Surfnet\StepupMiddleware\ApiBundle\Request\CommandParamConverter;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Root\Command\FooBarCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Root\Command\Ns\QuuxCommand;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class CommandParamConverterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @test
     * @group api-bundle
     * @dataProvider invalidCommandJsonStructures
     * @param string $commandJson
     */
    public function it_validates_the_command_structure($commandJson): void
    {
        $this->expectException(BadCommandRequestException::class);

        /** @var Request&MockInterface $request */
        $request = m::mock(Request::class)
            ->shouldReceive('getContent')->with()->andReturn($commandJson)
            ->getMock();

        /** @var ParamConverter&MockInterface $configuration */
        $configuration = m::mock(ParamConverter::class);

        $converter = new CommandParamConverter();
        $converter->apply($request, $configuration);
    }

    /**
     * @test
     * @group api-bundle
     * @dataProvider convertibleCommandNames
     * @param string $expectedCommandClass
     */
    public function it_can_convert_command_name_notation($expectedCommandClass, string $commandName): void
    {
        $command = ['command' => ['name' => $commandName, 'uuid' => 'abcdef', 'payload' => new stdClass]];

        /** @var Request&MockInterface $request */
        $request = m::mock(Request::class)
            ->shouldReceive('getContent')->with()->andReturn(json_encode($command))
            ->getMock();

        /** @var ParameterBag&MockInterface $attributes */
        $attributes = m::mock()
            ->shouldReceive('set')->with('command', m::type($expectedCommandClass))
            ->getMock();
        $request->attributes = $attributes;
        $configuration = m::mock(ParamConverter::class);

        $converter = new CommandParamConverter();
        $converter->apply($request, $configuration);

        $this->assertInstanceOf(CommandParamConverter::class, $converter);
    }

    /**
     * @test
     * @group api-bundle
     */
    public function it_sets_uuid(): void
    {
        $command = ['command' => ['name' => 'Root:FooBar', 'uuid' => 'abcdef', 'payload' => new stdClass]];

        /** @var Request $request */
        $request = m::mock(Request::class)
            ->shouldReceive('getContent')->with()->andReturn(json_encode($command))
            ->getMock();

        /** @var ParameterBag $attributes */
        $attributes = m::mock()
            ->shouldReceive('set')->with('command', $this->spy($spiedCommand))
            ->getMock();
        $request->attributes = $attributes;

        $configuration = m::mock(ParamConverter::class);

        $converter = new CommandParamConverter();
        $converter->apply($request, $configuration);

        $this->assertEquals('abcdef', $spiedCommand->UUID, 'UUID mismatch');
    }

    /**
     * @test
     * @group api-bundle
     */
    public function it_sets_payload(): void
    {
        $command = ['command' => ['name' => 'Root:FooBar', 'uuid' => 'abcdef', 'payload' => ['snake_case' => true]]];

        /** @var Request $request */
        $request = m::mock(Request::class)
            ->shouldReceive('getContent')->with()->andReturn(json_encode($command))
            ->getMock();

        /** @var ParameterBag $attributes */
        $attributes = m::mock()
            ->shouldReceive('set')->with('command', $this->spy($spiedCommand))
            ->getMock();

        $request->attributes = $attributes;

        $configuration = m::mock(ParamConverter::class);

        $converter = new CommandParamConverter();
        $converter->apply($request, $configuration);

        $spiedPayload = (array)$spiedCommand;
        unset($spiedPayload['UUID']);
        $this->assertSame(['snakeCase' => true], $spiedPayload, 'Payload mismatch');
    }

    public function invalidCommandJsonStructures(): array
    {
        return array_map(
            fn($command): array => [json_encode($command)],
            [
                'Body may not be null' => null,
                'Body may not be integer' => 1,
                'Body may not be float' => 1.1,
                'Body may not be array' => [],
                'Object must contain command property' => new stdClass,
                'Command may not be null' => ['command' => null],
                'Command may not be integer' => ['command' => 1],
                'Command may not be float' => ['command' => 1.1],
                'Command may not be array' => ['command' => []],
                'Command must contain name' => ['command' => ['uuid' => 'foo', 'payload' => 'bar']],
                'Command must contain uuid' => ['command' => ['name' => 'quux', 'payload' => 'wibble']],
                'Command must contain payload' => ['command' => ['name' => 'wobble', 'uuid' => 'wubble']],
                'Command payload may not be null' => ['command' => ['payload' => null]],
                'Command payload may not be integer' => ['command' => ['payload' => 1]],
                'Command payload may not be float' => ['command' => ['payload' => 1.1]],
                'Command payload may not be array' => ['command' => ['payload' => []]],
            ],
        );
    }

    public function convertibleCommandNames(): array
    {
        return [
            'It can convert simple command notation with a namespace' => [
                FooBarCommand::class,
                'Root:FooBar',
            ],
            'It can convert simple command notation with a namespace with trailing backslash' => [
                FooBarCommand::class,
                'Root:FooBar',
            ],
            'It can convert namespaced command notation with a namespace' => [
                QuuxCommand::class,
                'Root:Ns.Quux',
            ],
        ];
    }

    /**
     * @return MatcherAbstract
     */
    private function spy(mixed &$spy)
    {
        return m::on(
            function ($value) use (&$spy): bool {
                $spy = $value;

                return true;
            },
        );
    }
}
