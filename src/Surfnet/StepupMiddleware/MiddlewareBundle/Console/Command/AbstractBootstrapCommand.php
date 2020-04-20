<?php

/**
 * Copyright 2020 SURFnet bv
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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Console\Command;

use Broadway\EventHandling\EventBusInterface;
use Rhumsaa\Uuid\Uuid;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\InstitutionConfigurationOptionsRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\UnverifiedSecondFactorRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\VerifiedSecondFactorRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\VetSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline;
use Surfnet\StepupMiddleware\MiddlewareBundle\Service\DBALConnectionHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

abstract class AbstractBootstrapCommand extends Command
{
    /** @var Pipeline  */
    protected $pipeline;
    /** @var EventBusInterface  */
    protected $eventBus;
    /** @var DBALConnectionHelper  */
    protected $connection;
    /** @var IdentityRepository  */
    protected $identityRepository;
    /** @var UnverifiedSecondFactorRepository  */
    protected $unverifiedSecondFactorRepository;
    /** @var VerifiedSecondFactorRepository */
    protected $verifiedSecondFactorRepository;
    /** @var InstitutionConfigurationOptionsRepository */
    private $institutionConfigurationRepository;
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    public function __construct(
        Pipeline $pipeline,
        EventBusInterface $eventBus,
        DBALConnectionHelper $connection,
        IdentityRepository $identityRepository,
        UnverifiedSecondFactorRepository $unverifiedSecondFactorRepository,
        VerifiedSecondFactorRepository $verifiedSecondFactorRepository,
        InstitutionConfigurationOptionsRepository $institutionConfigurationOptionsRepository,
        TokenStorageInterface $tokenStorage
    ) {
        $this->pipeline = $pipeline;
        $this->eventBus = $eventBus;
        $this->connection = $connection;
        $this->identityRepository = $identityRepository;
        $this->unverifiedSecondFactorRepository = $unverifiedSecondFactorRepository;
        $this->verifiedSecondFactorRepository = $verifiedSecondFactorRepository;
        $this->institutionConfigurationRepository = $institutionConfigurationOptionsRepository;
        $this->tokenStorage = $tokenStorage;
    }

    protected function requiresMailVerification(Institution $institution)
    {
        $configuration = $this->institutionConfigurationRepository->findConfigurationOptionsFor($institution);
        if ($configuration) {
            return $configuration->verifyEmailOption->isEnabled();
        }
        return true;
    }

    protected function vetSecondFactor($tokenType, $actorId, $identity, $secondFactorId, $verifiedSecondFactor, $phoneNumber)
    {
        $command = new VetSecondFactorCommand();
        $command->UUID = (string) Uuid::uuid4();
        $command->authorityId = $actorId;
        $command->identityId = $identity->id;
        $command->secondFactorId = $secondFactorId;
        $command->registrationCode = $verifiedSecondFactor->registrationCode;
        $command->secondFactorType = $tokenType;
        $command->secondFactorIdentifier = $phoneNumber;
        $command->documentNumber = '123987';
        $command->identityVerified = true;
        $this->pipeline->process($command);
    }
}
