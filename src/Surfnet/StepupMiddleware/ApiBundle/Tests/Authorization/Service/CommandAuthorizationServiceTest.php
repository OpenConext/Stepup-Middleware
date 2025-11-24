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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\RegistrationAuthorityRole;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Service\AuthorizationContextService;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Service\CommandAuthorizationService;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContext;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\IdentityService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\WhitelistService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RegistrationAuthorityCredentials;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\AbstractCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\RaExecutable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\SelfAsserted;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\SelfServiceExecutable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\AddRaLocationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\ChangeRaLocationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\CreateInstitutionConfigurationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\ReconfigureInstitutionConfigurationOptionsCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\RemoveInstitutionConfigurationByUnnormalizedIdCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\RemoveRaLocationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\UpdateConfigurationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\AccreditIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\AddToWhitelistCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\AmendRegistrationAuthorityInformationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\AppointRoleCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\BootstrapIdentityWithYubikeySecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\CreateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ExpressLocalePreferenceCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ForgetIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\MigrateVettedSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\PromiseSafeStoreSecretTokenPossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProveGssfPossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProvePhonePossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProvePhoneRecoveryTokenPossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProveU2fDevicePossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProveYubikeyPossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RegisterSelfAssertedSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RemoveFromWhitelistCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ReplaceWhitelistCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RetractRegistrationAuthorityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RevokeOwnRecoveryTokenCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RevokeOwnSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RevokeRegistrantsRecoveryTokenCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RevokeRegistrantsSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\SaveVettingTypeHintCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\SelfVetSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\SendSecondFactorRegistrationEmailCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\SendVerifiedSecondFactorRemindersCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\UpdateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\VerifyEmailCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\VetSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\Command\FixedUuidStubCommand;
use function is_array;
use function is_string;
use function property_exists;

class CommandAuthorizationServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private IdentityService&MockInterface $identityService;

    private LoggerInterface&MockInterface $logger;

    private AuthorizationContextService&MockInterface $authorizationContextService;

    private CommandAuthorizationService $service;

    public function setUp(): void
    {
        $whitelistService = m::mock(WhitelistService::class);
        $identityService = m::mock(IdentityService::class);
        $logger = m::mock(LoggerInterface::class);
        $authorizationContextService = m::mock(AuthorizationContextService::class);

        $service = new CommandAuthorizationService(
            $whitelistService,
            $identityService,
            $logger,
            $authorizationContextService,
        );

        $this->identityService = $identityService;
        $this->logger = $logger;
        $this->authorizationContextService = $authorizationContextService;

        $this->service = $service;
    }

    public function test_shared_ra_and_ss_commands_are_correctly_authorized(): void
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

    #[Test]
    #[DataProvider('availableCommands')]
    public function a_sraa_should_be_able_to_execute_all_commands(string $file, Command $command): void
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


    #[Test]
    #[DataProvider('availableCommands')]
    public function an_identity_should_be_able_to_execute_own_selfservice_commands(string $file, mixed $command): void
    {
        $this->assertInstanceOf(Command::class, $command);

        if ($command instanceof SelfServiceExecutable && !$command instanceof RaExecutable) {
            $actorId = new IdentityId('123');
            $actorInstitution = new Institution('institution');

            /** @var SelfServiceExecutable&AbstractCommand&MockInterface $command */
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

    #[Test]
    #[DataProvider('availableCommands')]
    public function an_identity_should_be_able_to_execute_configured_ra_commands(string $file, mixed $command): void
    {
        $this->assertInstanceOf(Command::class, $command);

        if ($command instanceof RaExecutable && !$command instanceof SelfServiceExecutable) {
            $actorId = new IdentityId('123');
            $actorInstitution = new Institution('institution');

            /** @var RaExecutable&AbstractCommand&MockInterface $command */
            $command = m::mock($command);
            if (property_exists($command, 'identityId')) {
                $command->identityId = $actorId;
            }
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
                false,
            );

            $role = RegistrationAuthorityRole::raa();
            if ($command instanceof VetSecondFactorCommand
                || $command instanceof RevokeRegistrantsSecondFactorCommand
                || $command instanceof RevokeRegistrantsRecoveryTokenCommand
            ) {
                $role = RegistrationAuthorityRole::ra();
                $mockInstitution = new Institution('mock institution');
                $mockIdentity = m::mock(Identity::class);
                $mockIdentity->institution = $mockInstitution;
                $this->identityService->shouldReceive('find')
                    ->with($command->identityId)
                    ->andReturn($mockIdentity);
            }

            $this->authorizationContextService->shouldReceive('buildInstitutionAuthorizationContext')
                ->with($actorId, m::on(fn($arg): bool => $arg == $role))
                ->andReturn($authorizationContext);

            $this->assertTrue($this->service->maySelfServiceCommandBeExecutedOnBehalfOf($command, $actorId));
            $this->assertTrue($this->service->mayRaCommandBeExecutedOnBehalfOf($command, $actorId, $actorInstitution));
        }
    }


    #[Test]
    #[DataProvider('availableCommands')]
    public function an_identity_should_be_able_to_execute_configured_ra_and_selfservice_commands(string $file, mixed $command): void
    {
        $this->assertInstanceOf(Command::class, $command);

        if ($command instanceof RaExecutable && $command instanceof SelfServiceExecutable) {
            $actorId = new IdentityId('123');
            $actorInstitution = new Institution('institution');

            /** @var RaExecutable&AbstractCommand&MockInterface $command */
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
                false,
            );

            $role = RegistrationAuthorityRole::raa();

            if ($command instanceof VetSecondFactorCommand || $command instanceof RevokeRegistrantsSecondFactorCommand) {
                $role = RegistrationAuthorityRole::ra();
            }

            $this->authorizationContextService->shouldReceive('buildInstitutionAuthorizationContext')
                ->with($actorId, m::on(fn($arg): bool => $arg == $role))
                ->andReturn($authorizationContext);


            $this->service->maySelfServiceCommandBeExecutedOnBehalfOf($command, $actorId);
            $this->service->mayRaCommandBeExecutedOnBehalfOf($command, $actorId, $actorInstitution);
        }
    }


    #[Test]
    #[DataProvider('availableCommands')]
    public function an_identity_should_not_be_able_to_execute_someone_elses_selfservice_commands(string $file, mixed $command): void
    {
        $this->assertInstanceOf(Command::class, $command);

        if ($command instanceof SelfServiceExecutable && !$command instanceof RaExecutable) {
            $actorId = new IdentityId('123');
            $actorInstitution = new Institution('institution');

            /** @var SelfServiceExecutable&AbstractCommand&MockInterface $command */
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
                $command instanceof SelfAsserted
            ) {
                $this->assertTrue($this->service->maySelfServiceCommandBeExecutedOnBehalfOf($command, $actorId));
                return;
            }

            $this->assertFalse($this->service->maySelfServiceCommandBeExecutedOnBehalfOf($command, $actorId));
            $this->assertTrue($this->service->mayRaCommandBeExecutedOnBehalfOf($command, $actorId, $actorInstitution));
        }
    }

    #[Test]
    #[DataProvider('availableCommands')]
    public function an_identity_should_be_able_to_execute_unconfigured_ra_commands(string $file, mixed $command): void
    {
        $this->assertInstanceOf(Command::class, $command);

        if ($command instanceof RaExecutable && !$command instanceof SelfServiceExecutable) {
            $actorId = new IdentityId('123');
            $actorInstitution = new Institution('institution');

            /** @var RaExecutable&AbstractCommand&MockInterface $command */
            $command = m::mock($command);
            $command->shouldReceive('getRaInstitution')
                ->andReturn($actorInstitution->getInstitution());

            $this->logger->shouldIgnoreMissing();

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
                false,
            );

            $role = RegistrationAuthorityRole::raa();
            if ($command instanceof VetSecondFactorCommand
                || $command instanceof RevokeRegistrantsSecondFactorCommand
                || $command instanceof RevokeRegistrantsRecoveryTokenCommand
            ) {
                if (property_exists($command, 'identityId')) {
                    $command->identityId = $actorId;
                }
                $role = RegistrationAuthorityRole::ra();
                $mockInstitution = new Institution('mock institution');
                $mockIdentity = m::mock(Identity::class);
                $mockIdentity->institution = $mockInstitution;
                $this->identityService->shouldReceive('find')
                    ->with($command->identityId)
                    ->andReturn($mockIdentity);
            }

            $this->authorizationContextService->shouldReceive('buildInstitutionAuthorizationContext')
                ->with($actorId, m::on(fn($arg): bool => $arg == $role))
                ->andReturn($authorizationContext);

            $this->assertTrue($this->service->maySelfServiceCommandBeExecutedOnBehalfOf($command, $actorId));
            $this->assertFalse($this->service->mayRaCommandBeExecutedOnBehalfOf($command, $actorId, $actorInstitution));
        }
    }


    #[Test]
    public function all_available_commands_should_be_tested(): void
    {
        $tested = [
            AddRaLocationCommand::class,
            ChangeRaLocationCommand::class,
            CreateInstitutionConfigurationCommand::class,
            ReconfigureInstitutionConfigurationOptionsCommand::class,
            RemoveInstitutionConfigurationByUnnormalizedIdCommand::class,
            RemoveRaLocationCommand::class,
            UpdateConfigurationCommand::class,
            AccreditIdentityCommand::class,
            AddToWhitelistCommand::class,
            AmendRegistrationAuthorityInformationCommand::class,
            AppointRoleCommand::class,
            BootstrapIdentityWithYubikeySecondFactorCommand::class,
            CreateIdentityCommand::class,
            ExpressLocalePreferenceCommand::class,
            ForgetIdentityCommand::class,
            MigrateVettedSecondFactorCommand::class,
            PromiseSafeStoreSecretTokenPossessionCommand::class,
            ProveGssfPossessionCommand::class,
            ProvePhonePossessionCommand::class,
            ProvePhoneRecoveryTokenPossessionCommand::class,
            ProveU2fDevicePossessionCommand::class,
            ProveYubikeyPossessionCommand::class,
            RegisterSelfAssertedSecondFactorCommand::class,
            RemoveFromWhitelistCommand::class,
            ReplaceWhitelistCommand::class,
            RetractRegistrationAuthorityCommand::class,
            RevokeOwnRecoveryTokenCommand::class,
            RevokeOwnSecondFactorCommand::class,
            RevokeRegistrantsRecoveryTokenCommand::class,
            RevokeRegistrantsSecondFactorCommand::class,
            SaveVettingTypeHintCommand::class,
            SelfVetSecondFactorCommand::class,
            SendSecondFactorRegistrationEmailCommand::class,
            SendVerifiedSecondFactorRemindersCommand::class,
            UpdateIdentityCommand::class,
            VerifyEmailCommand::class,
            VetSecondFactorCommand::class,
            FixedUuidStubCommand::class,
        ];

        $available = $this->availableCommands();

        $classNames = [];
        foreach ($available as $command) {
            $classNames[] = $command[1]::class;
        }

        $this->assertSame($tested, $classNames);
    }


    /**
     * @return string[][]|Command[][]
     */
    public static function availableCommands(): array
    {
        $rootPath = realpath(__DIR__ . '/../../../../../../../src');
        assert(is_string($rootPath), 'Root path could not be determined correctly');
        $basePath = realPath($rootPath . '/Surfnet/StepupMiddleware/CommandHandlingBundle') . '/*';

        $commands = [];

        // get folders
        $folders = glob($basePath, GLOB_ONLYDIR);
        assert(is_array($folders), 'Unable to grab the CommandHandlingBundle folders');
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
