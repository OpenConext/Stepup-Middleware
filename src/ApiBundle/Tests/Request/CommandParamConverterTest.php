<?php

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Request;

use Mockery as m;
use Surfnet\StepupMiddleware\ApiBundle\Request\CommandParamConverter;

class CommandParamConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider invalidCommandJsonStructures
     * @param string $commandJson
     */
    public function testItValidatesTheCommandStructure($commandJson)
    {
        $this->setExpectedException('Surfnet\StepupMiddleware\ApiBundle\Exception\BadCommandRequestException');

        $request = m::mock('Symfony\Component\HttpFoundation\Request')
            ->shouldReceive('getContent')->with()->andReturn($commandJson)
            ->getMock();
        $configuration = m::mock('Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter')
            ->shouldReceive('getOptions')->with()->andReturn(['namespace' => 'Surfnet'])
            ->getMock();

        $converter = new CommandParamConverter();
        $converter->apply($request, $configuration);
    }

    /**
     * @dataProvider convertibleCommandNames
     * @param string $expectedCommandClass
     * @param string $commandName
     * @param string $namespace
     */
    public function testItCanConvertCommandNameNotation($expectedCommandClass, $commandName, $namespace)
    {
        require_once(__DIR__ . '/commands.php');

        $command = ['command' => ['name' => $commandName, 'uuid' => 'abcdef', 'payload' => new \stdClass]];

        $request = m::mock('Symfony\Component\HttpFoundation\Request')
            ->shouldReceive('getContent')->with()->andReturn(json_encode($command))
            ->getMock();
        $request->attributes = m::mock()
            ->shouldReceive('set')->with('command', m::type($expectedCommandClass))
            ->getMock();
        $configuration = m::mock('Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter')
            ->shouldReceive('getOptions')->with()->andReturn(['namespace' => $namespace])
            ->getMock();

        $converter = new CommandParamConverter();
        $converter->apply($request, $configuration);
    }

    public function testItSetsUuid()
    {
        require_once(__DIR__ . '/commands.php');

        $command = ['command' => ['name' => 'Root:FooBar', 'uuid' => 'abcdef', 'payload' => new \stdClass]];

        $request = m::mock('Symfony\Component\HttpFoundation\Request')
            ->shouldReceive('getContent')->with()->andReturn(json_encode($command))
            ->getMock();
        $request->attributes = m::mock()
            ->shouldReceive('set')->with('command', self::spy($spiedCommand))
            ->getMock();
        $configuration = m::mock('Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter')
            ->shouldReceive('getOptions')->with()->andReturn(['namespace' => 'My\Ns'])
            ->getMock();

        $converter = new CommandParamConverter();
        $converter->apply($request, $configuration);

        $this->assertEquals('abcdef', $spiedCommand->UUID, 'UUID mismatch');
    }

    public function testItSetsPayload()
    {
        require_once(__DIR__ . '/commands.php');

        $command = ['command' => ['name' => 'Root:FooBar', 'uuid' => 'abcdef', 'payload' => ['snake_case' => true]]];

        $request = m::mock('Symfony\Component\HttpFoundation\Request')
            ->shouldReceive('getContent')->with()->andReturn(json_encode($command))
            ->getMock();
        $request->attributes = m::mock()
            ->shouldReceive('set')->with('command', self::spy($spiedCommand))
            ->getMock();
        $configuration = m::mock('Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter')
            ->shouldReceive('getOptions')->with()->andReturn(['namespace' => 'My\Ns'])
            ->getMock();

        $converter = new CommandParamConverter();
        $converter->apply($request, $configuration);

        $spiedPayload = (array) $spiedCommand;
        unset($spiedPayload['UUID']);
        $this->assertSame(['snakeCase' => true], $spiedPayload, 'Payload mismatch');
    }

    public function invalidCommandJsonStructures()
    {
        return array_map(
            function ($command) {
                return [json_encode($command)];
            },
            [
                'Body may not be null' => null,
                'Body may not be integer' => 1,
                'Body may not be float' => 1.1,
                'Body may not be array' => [],
                'Object must contain command property' => new \stdClass,
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
            ]
        );
    }

    public function convertibleCommandNames()
    {
        return [
            'It can convert simple command notation with a namespace' => [
                'My\Ns\Root\Command\FooBarCommand', 'Root:FooBar', 'My\\Ns',
            ],
            'It can convert simple command notation with a namespace with trailing backslash' => [
                'My\Ns\Root\Command\FooBarCommand', 'Root:FooBar', 'My\\Ns\\',
            ],
            'It can convert namespaced command notation with a namespace' => [
                'My\Ns\Root\Command\Ns\QuuxCommand', 'Root:Ns.Quux', 'My\\Ns',
            ],
        ];
    }

    /**
     * @param mixed &$spy
     * @return \Mockery\Matcher\MatcherAbstract
     */
    private static function spy(&$spy)
    {
        return m::on(
            function ($value) use (&$spy) {
                $spy = $value;

                return true;
            }
        );
    }
}
