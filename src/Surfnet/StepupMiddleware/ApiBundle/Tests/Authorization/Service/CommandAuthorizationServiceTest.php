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
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Rhumsaa\Uuid\Uuid;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Service\AuthorizationContextService;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Service\CommandAuthorizationService;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContext;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\IdentityService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\WhitelistService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RegistrationAuthorityCredentials;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\RaExecutable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\SelfServiceExecutable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\CreateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ExpressLocalePreferenceCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RevokeRegistrantsSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\SelfVetSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\UpdateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\VetSecondFactorCommand;

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

    public function setUp(): void
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

    public function test_shared_ra_and_ss_commands_are_correctly_authorized()
    {
        $actorId = new IdentityId('123');
        $actorInstitution = new Institution('institution');

        $this->logger->shouldReceive('notice');

        $raCredentials = m::mock(RegistrationAuthorityCredentials::class);
        $raCredentials->shouldReceive('isSraa')
            ->andReturn(false);

        $this->identityService->shouldReceive('findRegistrationAuthorityCredentialsOf')
            ->with($actorId->getIdentityId())
            ->andReturn($raCredentials);

        $command = new ExpressLocalePreferenceCommand();
        $command->identityId = $actorId->getIdentityId();
        $command->UUID = Uuid::uuid4();

        $this->assertTrue($this->service->maySelfServiceCommandBeExecutedOnBehalfOf($command, $actorId));
        $this->assertTrue($this->service->mayRaCommandBeExecutedOnBehalfOf($command, $actorId, $actorInstitution));
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
     * @dataProvider availableCommands
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

            $role = InstitutionRole::useRaa();
            if ($command instanceof VetSecondFactorCommand || $command instanceof RevokeRegistrantsSecondFactorCommand) {
                $role = InstitutionRole::useRa();
                $mockInstitution = new Institution('mock institution');
                $mockIdentity = m::mock(Identity::class);
                $mockIdentity->institution = $mockInstitution;
                $this->identityService->shouldReceive('find')
                    ->with($command->identityId)
                    ->andReturn($mockIdentity);
            }

            $this->authorizationContextService->shouldReceive('buildInstitutionAuthorizationContext')
                ->with($actorId, m::on(function($arg) use ($role){
                    return $arg == $role;
                }))
                ->andReturn($authorizationContext);

            $this->assertTrue($this->service->maySelfServiceCommandBeExecutedOnBehalfOf($command, $actorId));
            $this->assertTrue($this->service->mayRaCommandBeExecutedOnBehalfOf($command, $actorId, $actorInstitution));
        }
    }


    /**
     * @test
     * @dataProvider availableCommands
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

            $role = InstitutionRole::useRaa();
            if ($command instanceof VetSecondFactorCommand || $command instanceof RevokeRegistrantsSecondFactorCommand) {
                $role = InstitutionRole::useRa();
            }

            $this->authorizationContextService->shouldReceive('buildInstitutionAuthorizationContext')
                ->with($actorId, m::on(function($arg) use ($role){
                    return $arg == $role;
                }))
                ->andReturn($authorizationContext);


            $this->service->maySelfServiceCommandBeExecutedOnBehalfOf($command, $actorId);
            $this->service->mayRaCommandBeExecutedOnBehalfOf($command, $actorId, $actorInstitution);
        }
    }


    /**
     * @test
     * @dataProvider availableCommands
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
            if ($command instanceof CreateIdentityCommand ||
                $command instanceof UpdateIdentityCommand ||
                $command instanceof SelfVetSecondFactorCommand
            ) {
                $this->assertTrue($this->service->maySelfServiceCommandBeExecutedOnBehalfOf($command, $actorId));
                return;
            }

            $this->assertFalse($this->service->maySelfServiceCommandBeExecutedOnBehalfOf($command, $actorId));
            $this->assertTrue($this->service->mayRaCommandBeExecutedOnBehalfOf($command, $actorId, $actorInstitution));
        }
    }

    /**
     * @test
     * @dataProvider availableCommands
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

            $role = InstitutionRole::useRaa();
            if ($command instanceof VetSecondFactorCommand || $command instanceof RevokeRegistrantsSecondFactorCommand) {
                $role = InstitutionRole::useRa();

                $mockInstitution = new Institution('mock institution');
                $mockIdentity = m::mock(Identity::class);
                $mockIdentity->institution = $mockInstitution;
                $this->identityService->shouldReceive('find')
                    ->with($command->identityId)
                    ->andReturn($mockIdentity);
            }

            $this->authorizationContextService->shouldReceive('buildInstitutionAuthorizationContext')
                ->with($actorId, m::on(function($arg) use ($role) {
                    return $arg == $role;
                }))
                ->andReturn($authorizationContext);

            $this->assertTrue($this->service->maySelfServiceCommandBeExecutedOnBehalfOf($command, $actorId));
            $this->assertFalse($this->service->mayRaCommandBeExecutedOnBehalfOf($command, $actorId, $actorInstitution));
        }
    }

    public function test_an_raa_should_be_able_to_perform_ra_tasks()
    {
        $command = new VetSecondFactorCommand();
        $this->assertInstanceOf(Command::class, $command);

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
            new Institution('institution'),
        ]);

        $authorizationContext = new InstitutionAuthorizationContext(
            $institutionCollection,
            false
        );

        // At this point, in code the role is downplayed to useRa. Making it impossible for an insittution with only
        // useRaa to vet tokens.
        $role = InstitutionRole::useRaa();

        $this->authorizationContextService->shouldReceive('buildInstitutionAuthorizationContext')
            ->with($actorId, m::on(function($arg) use ($role) {
                return $arg == $role;
            }))
            ->andReturn($authorizationContext);

        $this->assertTrue($this->service->mayRaCommandBeExecutedOnBehalfOf($command, $actorId, $actorInstitution));
    }



    /**
     * @test
     *
     * @param mixed $value
     */
    public function all_available_commands_should_be_tested()
    {
        $tested = array (
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Configuration\\Command\\AddRaLocationCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Configuration\\Command\\ChangeRaLocationCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Configuration\\Command\\CreateInstitutionConfigurationCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Configuration\\Command\\ReconfigureInstitutionConfigurationOptionsCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Configuration\\Command\\RemoveInstitutionConfigurationByUnnormalizedIdCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Configuration\\Command\\RemoveRaLocationCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Configuration\\Command\\UpdateConfigurationCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\AccreditIdentityCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\AddToWhitelistCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\AmendRegistrationAuthorityInformationCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\AppointRoleCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\BootstrapIdentityWithYubikeySecondFactorCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\CreateIdentityCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\ExpressLocalePreferenceCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\ForgetIdentityCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\MigrateVettedSecondFactorCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\ProveGssfPossessionCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\ProvePhonePossessionCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\ProveU2fDevicePossessionCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\ProveYubikeyPossessionCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\RemoveFromWhitelistCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\ReplaceWhitelistCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\RetractRegistrationAuthorityCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\RevokeOwnSecondFactorCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\RevokeRegistrantsSecondFactorCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\SelfVetSecondFactorCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\SendVerifiedSecondFactorRemindersCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\UpdateIdentityCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\VerifyEmailCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Identity\\Command\\VetSecondFactorCommand',
            'Surfnet\\StepupMiddleware\\CommandHandlingBundle\\Tests\\Command\\FixedUuidStubCommand',
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
