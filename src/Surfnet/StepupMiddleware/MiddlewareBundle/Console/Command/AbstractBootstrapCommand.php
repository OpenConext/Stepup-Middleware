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
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command as MiddlewareCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Metadata;
use Surfnet\StepupMiddleware\CommandHandlingBundle\EventSourcing\MetadataEnricher;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\VerifyEmailCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\VetSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline;
use Surfnet\StepupMiddleware\MiddlewareBundle\Exception\InvalidArgumentException;
use Surfnet\StepupMiddleware\MiddlewareBundle\Service\DBALConnectionHelper;
use Surfnet\StepupMiddleware\MiddlewareBundle\Service\TokenBootstrapService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

abstract class AbstractBootstrapCommand extends Command
{
    /** @var Pipeline  */
    private $pipeline;
    /** @var EventBusInterface  */
    private $eventBus;
    /** @var DBALConnectionHelper  */
    private $connection;
    /** @var TokenStorageInterface */
    protected $tokenStorage;
    /** @var MetadataEnricher */
    private $enricher;
    /** @var TokenBootstrapService */
    protected $tokenBootstrapService;

    private $validRegistrationStatuses = ['unverified', 'verified', 'vetted'];

    public function __construct(
        Pipeline $pipeline,
        EventBusInterface $eventBus,
        DBALConnectionHelper $connection,
        MetadataEnricher $enricher,
        TokenStorageInterface $tokenStorage,
        TokenBootstrapService $tokenBootstrapService
    ) {
        $this->pipeline = $pipeline;
        $this->eventBus = $eventBus;
        $this->connection = $connection;
        $this->enricher = $enricher;
        $this->tokenStorage = $tokenStorage;
        $this->tokenBootstrapService = $tokenBootstrapService;
        parent::__construct();
    }

    protected function beginTransaction()
    {
        $this->connection->beginTransaction();
    }

    protected function finishTransaction()
    {
        $this->eventBus->flush();
        $this->connection->commit();
    }

    protected function rollback()
    {
        $this->connection->rollBack();
    }

    protected function process(MiddlewareCommand $command)
    {
        $this->pipeline->process($command);
    }

    /**
     * @param string $registrationStatus
     * @return bool
     */
    protected function validRegistrationStatus($registrationStatus)
    {
        if (!in_array($registrationStatus, $this->validRegistrationStatuses)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid argument provided for the "registration-status" argument. One of: %s is expected. Received: "%s"',
                    implode(', ', $this->validRegistrationStatuses),
                    $registrationStatus
                )
            );
        }
    }

    protected function requiresMailVerification($institution)
    {
        $configuration = $this->tokenBootstrapService->findConfigurationOptionsFor($institution);
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

    protected function enrichEventMetadata($actorId)
    {
        $actor = $this->tokenBootstrapService->findIdentityById($actorId);
        $metadata = new Metadata();
        $metadata->actorId = $actor->id;
        $metadata->actorInstitution = $actor->institution;
        $this->enricher->setMetadata($metadata);
    }

    protected function verifyEmail($identity, $unverifiedSecondFactor)
    {
        $command = new VerifyEmailCommand();
        $command->UUID = (string)Uuid::uuid4();
        $command->identityId = $identity->id;
        $command->verificationNonce = $unverifiedSecondFactor->verificationNonce;
        $this->process($command);
    }
}
