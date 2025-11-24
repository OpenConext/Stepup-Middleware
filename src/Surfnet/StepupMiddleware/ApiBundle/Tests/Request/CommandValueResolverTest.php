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
use stdClass;
use Surfnet\StepupMiddleware\ApiBundle\Exception\BadCommandRequestException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command;
use Surfnet\StepupMiddleware\ApiBundle\Request\CommandValueResolver;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Root\Command\FooBarCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Root\Command\Ns\QuuxCommand;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class CommandValueResolverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidCommandJsonStructures')]
    #[\PHPUnit\Framework\Attributes\Group('api-bundle')]
    public function it_validates_the_command_structure(string $commandJson): void
    {
        $this->expectException(BadCommandRequestException::class);

        /** @var Request&MockInterface $request */
        $request = m::mock(Request::class)
            ->shouldReceive('getContent')->with()->andReturn($commandJson)
            ->getMock();

        /** @var ArgumentMetadata&MockInterface $argument */
        $argument = m::mock(ArgumentMetadata::class);
        $argument->shouldReceive('getType')
            ->once()
            ->andReturn(Command::class);

        $converter = new CommandValueResolver();
        $result = $converter->resolve($request, $argument);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(Command::class, $result[0]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('convertibleCommandNames')]
    #[\PHPUnit\Framework\Attributes\Group('api-bundle')]
    public function it_can_convert_command_name_notation(string $expectedCommandClass, string $commandName): void
    {
        $command = ['command' => ['name' => $commandName, 'uuid' => 'abcdef', 'payload' => new stdClass]];

        /** @var Request&MockInterface $request */
        $request = m::mock(Request::class)
            ->shouldReceive('getContent')->with()->andReturn(json_encode($command))
            ->getMock();

        /** @var ArgumentMetadata&MockInterface $argument */
        $argument = m::mock(ArgumentMetadata::class);
        $argument->shouldReceive('getType')
            ->once()
            ->andReturn(Command::class);

        $converter = new CommandValueResolver();
        $result = $converter->resolve($request, $argument);

        $this->assertCount(1, $result);
        $this->assertInstanceOf($expectedCommandClass, $result[0]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidCommandNames')]
    #[\PHPUnit\Framework\Attributes\Group('api-bundle')]
    public function it_fails_converting_invalid_command_name_notation(string $expectedCommandClass, string $commandName): void
    {
        $this->expectException(BadCommandRequestException::class);
        $this->expectExceptionMessage(sprintf('Command does not have a valid command name %s', $commandName));

        $command = ['command' => ['name' => $commandName, 'uuid' => 'abcdef', 'payload' => new stdClass]];

        /** @var Request&MockInterface $request */
        $request = m::mock(Request::class)
            ->shouldReceive('getContent')->with()->andReturn(json_encode($command))
            ->getMock();

        /** @var ArgumentMetadata&MockInterface $argument */
        $argument = m::mock(ArgumentMetadata::class);
        $argument->shouldReceive('getType')
            ->once()
            ->andReturn(Command::class);

        $converter = new CommandValueResolver();
        $converter->resolve($request, $argument);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('api-bundle')]
    public function it_sets_uuid(): void
    {
        $command = ['command' => ['name' => 'Root:FooBar', 'uuid' => 'abcdef', 'payload' => new stdClass]];

        /** @var Request $request */
        $request = m::mock(Request::class)
            ->shouldReceive('getContent')->with()->andReturn(json_encode($command))
            ->getMock();

        /** @var ArgumentMetadata&MockInterface $argument */
        $argument = m::mock(ArgumentMetadata::class);
        $argument->shouldReceive('getType')
            ->once()
            ->andReturn(Command::class);

        $converter = new CommandValueResolver();
        $result = $converter->resolve($request, $argument);

        $this->assertCount(1, $result);
        $this->assertEquals('abcdef', $result[0]->UUID, 'UUID mismatch');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('api-bundle')]
    public function it_sets_payload(): void
    {
        $command = ['command' => ['name' => 'Root:FooBar', 'uuid' => 'abcdef', 'payload' => ['snake_case' => true]]];

        /** @var Request $request */
        $request = m::mock(Request::class)
            ->shouldReceive('getContent')->with()->andReturn(json_encode($command))
            ->getMock();

        /** @var ArgumentMetadata&MockInterface $argument */
        $argument = m::mock(ArgumentMetadata::class);
        $argument->shouldReceive('getType')
            ->once()
            ->andReturn(Command::class);

        $converter = new CommandValueResolver();
        $result = $converter->resolve($request, $argument);

        $this->assertCount(1, $result);

        $spiedPayload = (array)$result[0];
        unset($spiedPayload['UUID']);

        $this->assertSame(['snakeCase' => true], $spiedPayload, 'Payload mismatch');
    }

    public static function invalidCommandJsonStructures(): array
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

    public static function convertibleCommandNames(): array
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

    public static function invalidCommandNames(): array
    {
        return [
            'It can not convert simple command notation with only a namespace' => [
                FooBarCommand::class,
                'Root',
            ],
            'It can not convert simple command notation without path' => [
                FooBarCommand::class,
                'Root:',
            ],
            'It can not convert empty command notation' => [
                FooBarCommand::class,
                '',
            ],
        ];
    }
}
