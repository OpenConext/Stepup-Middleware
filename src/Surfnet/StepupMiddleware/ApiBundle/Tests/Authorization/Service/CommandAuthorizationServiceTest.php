<?php

/**
 * Copyright 2018 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Authorization\Service;

use Mockery as m;
use PHPUnit_Framework_TestCase as TestCase;
use Psr\Log\LoggerInterface;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Service\AuthorizationContextService;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Service\CommandAuthorizationService;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContext;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\IdentityService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\WhitelistService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RegistrationAuthorityCredentials;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\RaExecutable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\SelfServiceExecutable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\CreateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\UpdateIdentityCommand;

class CommandAuthorizationServiceTest extends TestCase
{
    /**
     * @var WhitelistService|m\MockInterface
     */
    private $whitelistService;
    /**
     * @var IdentityService|m\MockInterface
     */
    private $identityService;
    /**
     * @var LoggerInterface|m\MockInterface
     */
    private $logger;
    /**
     * @var AuthorizationContextService|m\MockInterface
     */
    private $authorizationContextService;

    /**
     * @var CommandAuthorizationService
     */
    private $service;

    public function setUp()
    {
        $whitelistService = m::mock(WhitelistService::class);
        $identityService = m::mock(IdentityService::class);
        $logger = m::mock(LoggerInterface::class);
        $authorizationContextService = m::mock(AuthorizationContextService::class);

        $service = new CommandAuthorizationService($whitelistService, $identityService, $logger, $authorizationContextService);

        $this->whitelistService = $whitelistService;
        $this->identityService = $identityService;
        $this->logger = $logger;
        $this->authorizationContextService = $authorizationContextService;

        $this->service = $service;
    }

    /**
     * @test
     * @dataProvider availableCommands-
     *
     * @param mixed $value
     */
    public function a_sraa_should_be_able_to_execute_all_commands($file, $command)
    {
        $this->assertInstanceOf(Command::class, $command);

        $actorId = new IdentityId('123');
        $actorInstitution = new Institution('institution');

        $this->logger->shouldReceive('notice');

        $raCredentials = m::mock(RegistrationAuthorityCredentials::class);
        $raCredentials->shouldReceive('isSraa')
            ->andReturn(true);

        $this->identityService->shouldReceive('findRegistrationAuthorityCredentialsOf')
            ->with($actorId->getIdentityId())
            ->andReturn($raCredentials);

        $this->assertTrue($this->service->maySelfServiceCommandBeExecutedOnBehalfOf($command, $actorId));
        $this->assertTrue($this->service->mayRaCommandBeExecutedOnBehalfOf($command, $actorId, $actorInstitution));
    }


    /**
     * @test
     * @dataProvider availableCommands-
     *
     * @param mixed $value
     */
    public function an_identity_should_be_able_to_execute_own_selfservice_commands($file, $command)
    {
        $this->assertInstanceOf(Command::class, $command);

        if ($command instanceof SelfServiceExecutable && !$command instanceof  RaExecutable) {

            $actorId = new IdentityId('123');
            $actorInstitution = new Institution('institution');

            $command = m::mock($command);
            $command->shouldReceive('getIdentityId')
                ->andReturn($actorId->getIdentityId());

            $this->logger->shouldReceive('notice');

            $raCredentials = m::mock(RegistrationAuthorityCredentials::class);
            $raCredentials->shouldReceive('isSraa')
                ->andReturn(false);

            $this->identityService->shouldReceive('findRegistrationAuthorityCredentialsOf')
                ->with($actorId->getIdentityId())
                ->andReturn($raCredentials);


            $this->assertTrue($this->service->maySelfServiceCommandBeExecutedOnBehalfOf($command, $actorId));
            $this->assertTrue($this->service->mayRaCommandBeExecutedOnBehalfOf($command, $actorId, $actorInstitution));
        }
    }

    /**
     * @test
     * @dataProvider availableCommands-
     *
     * @param mixed $value
     */
    public function an_identity_should_be_able_to_execute_configured_ra_commands($file, $command)
    {
        $this->assertInstanceOf(Command::class, $command);

        if ($command instanceof RaExecutable && !$command instanceof  SelfServiceExecutable) {

            $actorId = new IdentityId('123');
            $actorInstitution = new Institution('institution');

            $command = m::mock($command);
            $command->shouldReceive('getRaInstitution')
                ->andReturn($actorInstitution->getInstitution());

            $this->logger->shouldReceive('notice');

            $raCredentials = m::mock(RegistrationAuthorityCredentials::class);
            $raCredentials->shouldReceive('isSraa')
                ->andReturn(false);

            $this->identityService->shouldReceive('findRegistrationAuthorityCredentialsOf')
                ->with($actorId->getIdentityId())
                ->andReturn($raCredentials);

            $institutionCollection = new InstitutionCollection([
                new Institution('mock institution'),
                $actorInstitution,
            ]);

            $authorizationContext = new InstitutionAuthorizationContext(
                $institutionCollection,
                false
            );

            $this->authorizationContextService->shouldReceive('buildInstitutionAuthorizationContext')
                ->with($actorId, m::on(function($arg) {
                    return $arg == InstitutionRole::useRaa();
                }))
                ->andReturn($authorizationContext);

            $this->assertTrue($this->service->maySelfServiceCommandBeExecutedOnBehalfOf($command, $actorId));
            $this->assertTrue($this->service->mayRaCommandBeExecutedOnBehalfOf($command, $actorId, $actorInstitution));
        }
    }


    /**
     * @test
     * @dataProvider availableCommands-
     *
     * @param mixed $value
     */
    public function an_identity_should_be_able_to_execute_configured_ra_and_selfservice_commands($file, $command)
    {
        $this->assertInstanceOf(Command::class, $command);

        if ($command instanceof RaExecutable && $command instanceof  SelfServiceExecutable) {

            $actorId = new IdentityId('123');
            $actorInstitution = new Institution('institution');

            $command = m::mock($command);
            $command->shouldReceive('getRaInstitution')
                ->andReturn($actorInstitution->getInstitution());

            $command->shouldReceive('getIdentityId')
                ->andReturn($actorId->getIdentityId());

            $this->logger->shouldReceive('notice');

            $raCredentials = m::mock(RegistrationAuthorityCredentials::class);
            $raCredentials->shouldReceive('isSraa')
                ->andReturn(false);

            $this->identityService->shouldReceive('findRegistrationAuthorityCredentialsOf')
                ->with($actorId->getIdentityId())
                ->andReturn($raCredentials);

            $institutionCollection = new InstitutionCollection([
                new Institution('mock institution'),
                $actorInstitution,
            ]);

            $authorizationContext = new InstitutionAuthorizationContext(
                $institutionCollection,
                false
            );

            $this->authorizationContextService->shouldReceive('buildInstitutionAuthorizationContext')
                ->with($actorId, m::on(function($arg) {
                    return $arg == InstitutionRole::useRaa();
                }))
                ->andReturn($authorizationContext);


            $this->service->maySelfServiceCommandBeExecutedOnBehalfOf($command, $actorId);
            $this->service->mayRaCommandBeExecutedOnBehalfOf($command, $actorId, $actorInstitution);
        }
    }


    /**
     * @test
     * @dataProvider availableCommands-
     *
     * @param mixed $value
     */
    public function an_identity_should_not_be_able_to_execute_someone_elses_selfservice_commands($file, $command)
    {
        $this->assertInstanceOf(Command::class, $command);

        if ($command instanceof SelfServiceExecutable && !$command instanceof  RaExecutable) {

            $actorId = new IdentityId('123');
            $actorInstitution = new Institution('institution');

            $command = m::mock($command);
            $command->shouldReceive('getIdentityId')
                ->andReturn(new IdentityId('someone else'));

            $this->logger->shouldReceive('notice');

            $raCredentials = m::mock(RegistrationAuthorityCredentials::class);
            $raCredentials->shouldReceive('isSraa')
                ->andReturn(false);

            $this->identityService->shouldReceive('findRegistrationAuthorityCredentialsOf')
                ->with($actorId->getIdentityId())
                ->andReturn($raCredentials);

            // These commands should always be allowed to be executed
            if ($command instanceof CreateIdentityCommand || $command instanceof UpdateIdentityCommand) {
                $this->assertTrue($this->service->maySelfServiceCommandBeExecutedOnBehalfOf($command, $actorId));
                return;
            }

            $this->assertFalse($this->service->maySelfServiceCommandBeExecutedOnBehalfOf($command, $actorId));
            $this->assertTrue($this->service->mayRaCommandBeExecutedOnBehalfOf($command, $actorId, $actorInstitution));
        }
    }

    /**
     * @test
     * @dataProvider availableCommands-
     *
     * @param mixed $value
     */
    public function an_identity_should_be_able_to_execute_unconfigured_ra_commands($file, $command)
    {
        $this->assertInstanceOf(Command::class, $command);

        if ($command instanceof RaExecutable && !$command instanceof  SelfServiceExecutable) {

            $actorId = new IdentityId('123');
            $actorInstitution = new Institution('institution');

            $command = m::mock($command);
            $command->shouldReceive('getRaInstitution')
                ->andReturn($actorInstitution->getInstitution());

            $this->logger->shouldReceive('notice');

            $raCredentials = m::mock(RegistrationAuthorityCredentials::class);
            $raCredentials->shouldReceive('isSraa')
                ->andReturn(false);

            $this->identityService->shouldReceive('findRegistrationAuthorityCredentialsOf')
                ->with($actorId->getIdentityId())
                ->andReturn($raCredentials);

            $institutionCollection = new InstitutionCollection([
                new Institution('an other institution'),
            ]);

            $authorizationContext = new InstitutionAuthorizationContext(
                $institutionCollection,
                false
            );

            $this->authorizationContextService->shouldReceive('buildInstitutionAuthorizationContext')
                ->with($actorId, m::on(function($arg) {
                    return $arg == InstitutionRole::useRaa();
                }))
                ->andReturn($authorizationContext);

            $this->assertTrue($this->service->maySelfServiceCommandBeExecutedOnBehalfOf($command, $actorId));
            $this->assertFalse($this->service->mayRaCommandBeExecutedOnBehalfOf($command, $actorId, $actorInstitution));
        }
    }


    /**
     * @test
     *
     * @param mixed $value
     */
    public function all_available_commands_should_be_tested()
    {
        $tested = array (
            0 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Configuration\\Command\\AddRaLocationCommand',
            1 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Configuration\\Command\\ChangeRaLocationCommand',
            2 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Configuration\\Command\\CreateInstitutionConfigurationCommand',
            3 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Configuration\\Command\\ReconfigureInstitutionConfigurationOptionsCommand',
            4 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Configuration\\Command\\RemoveInstitutionConfigurationByUnnormalizedIdCommand',
            5 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Configuration\\Command\\RemoveRaLocationCommand',
            6 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Configuration\\Command\\UpdateConfigurationCommand',
            7 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\AccreditIdentityCommand',
            8 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\AddToWhitelistCommand',
            9 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\AmendRegistrationAuthorityInformationCommand',
            10 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\AppointRoleCommand',
            11 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\BootstrapIdentityWithYubikeySecondFactorCommand',
            12 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\CreateIdentityCommand',
            13 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\ExpressLocalePreferenceCommand',
            14 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\ForgetIdentityCommand',
            15 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\ProveGssfPossessionCommand',
            16 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\ProvePhonePossessionCommand',
            17 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\ProveU2fDevicePossessionCommand',
            18 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\ProveYubikeyPossessionCommand',
            19 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\RemoveFromWhitelistCommand',
            20 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\ReplaceWhitelistCommand',
            21 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\RetractRegistrationAuthorityCommand',
            22 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\RevokeOwnSecondFactorCommand',
            23 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\RevokeRegistrantsSecondFactorCommand',
            24 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\SendVerifiedSecondFactorRemindersCommand',
            25 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\UpdateIdentityCommand',
            26 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\VerifyEmailCommand',
            27 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\VetSecondFactorCommand',
            28 => 'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Tests\\Command\\FixedUuidStubCommand',
        );

        $available = $this->availableCommands();

        $classNames = [];
        foreach ($available as $command) {
            $classNames[] = get_class($command[1]);
        }

        $this->assertSame($tested, $classNames);
    }


    public function availableCommands()
    {
        $rootPath = realpath(__DIR__ . '/../../../../../../../src');
        $basePath = realPath($rootPath . '/Surfnet/StepupMiddleware/CommandHandlingBundle').'/*';

        $commands = [];

        // get folders
        $folders = glob($basePath, GLOB_ONLYDIR);
        foreach ($folders as $folder) {

            $commandPath = $folder . '/Command/*Command.php';
            $files = glob($commandPath);
            if ($files === false) {
                continue;
            }

            foreach ($files as $file) {
                $className = str_replace($rootPath, '', $file);
                $className = str_replace('.php', '', $className);
                $className = str_replace('/', '\\', $className);

                $command = new $className();
                if ($command instanceof Command) {
                    $commands[] = [
                        $file,
                        $command,
                    ];
                }
            }
        }


        return $commands;
    }
}
