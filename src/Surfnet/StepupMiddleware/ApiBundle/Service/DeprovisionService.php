<?php

/**
 * Copyright 2022 SURFnet bv
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

namespace Surfnet\StepupMiddleware\ApiBundle\Service;

use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Surfnet\Stepup\Identity\EventSourcing\IdentityRepository;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\ApiBundle\Exception\UserNotFoundException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository as ApiIdentityRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ForgetIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline;
use function sprintf;

class DeprovisionService implements DeprovisionServiceInterface
{
    private Pipeline $pipeline;

    private IdentityRepository $eventSourcingRepository;

    /**
     * @var ApiIdentityRepository
     */
    private ApiIdentityRepository $apiRepository;

    private LoggerInterface $logger;

    public function __construct(
        Pipeline              $pipeline,
        IdentityRepository    $eventSourcingRepository,
        ApiIdentityRepository $apiRepository,
        LoggerInterface       $logger
    ) {
        $this->pipeline = $pipeline;
        $this->eventSourcingRepository = $eventSourcingRepository;
        $this->apiRepository = $apiRepository;
        $this->logger = $logger;
    }

    public function readUserData(string $collabPersonId): array
    {
        try {
            $this->logger->debug(sprintf('Searching user identified by: %s', $collabPersonId));
            $identity = $this->getIdentityByNameId($collabPersonId);
            return $this->eventSourcingRepository->obtainInformation(new IdentityId($identity->id));
        } catch (UserNotFoundException $e) {
            $this->logger->notice(
                $e->getMessage()
            );
            return [];
        }
    }

    public function deprovision(string $collabPersonId): void
    {
        $this->logger->debug(sprintf('Searching user identified by: %s', $collabPersonId));
        try {
            $user = $this->getIdentityByNameId($collabPersonId);
        } catch (UserNotFoundException $e) {
            $this->logger->notice(
                $e->getMessage()
            );
            return;
        }
        $command = new ForgetIdentityCommand();
        $command->UUID = (string)Uuid::uuid4();
        $command->nameId = $collabPersonId;
        $command->institution = (string)$user->institution;
        $this->logger->debug('Processing the ForgetIdentityCommand');
        $this->pipeline->process($command);
    }

    private function getIdentityByNameId(string $collabPersonId): Identity
    {
        $user = $this->apiRepository->findOneByNameId($collabPersonId);
        if (!$user instanceof Identity) {
            throw new UserNotFoundException(
                sprintf(
                    'User identified by: %s was not found. Unable to provide deprovision data.',
                    $collabPersonId
                )
            );
        }
        return $user;
    }
}
