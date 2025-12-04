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
use RuntimeException;
use Surfnet\Stepup\Identity\EventSourcing\IdentityRepository;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Exception\UserNotFoundException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository as ApiIdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaListingRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\SraaRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ForgetIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline;
use function sprintf;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class DeprovisionService implements DeprovisionServiceInterface
{
    public function __construct(
        private readonly Pipeline                                                                           $pipeline,
        private readonly IdentityRepository                                                                 $eventSourcingRepository,
        private readonly ApiIdentityRepository                                                              $apiRepository,
        private readonly LoggerInterface                                                                    $logger,
        private readonly SraaRepository                                                                     $sraaRepository,
        private readonly RaListingRepository                                                                $raListingRepository,
    ) {
    }

    public function readUserData(string $collabPersonId): array
    {
        try {
            $this->logger->debug(sprintf('Searching user identified by: %s', $collabPersonId));
            $identity = $this->getIdentityByNameId($collabPersonId);
            return $this->eventSourcingRepository->obtainInformation(new IdentityId($identity->id));
        } catch (UserNotFoundException $e) {
            $this->logger->notice(
                $e->getMessage(),
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
                $e->getMessage(),
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
                    $collabPersonId,
                ),
            );
        }
        return $user;
    }

    public function assertIsAllowed(string $collabPersonId): void
    {
        $nameId = new NameId($collabPersonId);
        $identity = $this->apiRepository->findOneByNameId($nameId);

        if ($identity === null) {
            throw new RuntimeException('Cannot forget an identity that does not exist.');
        }

        if ($this->sraaRepository->contains($identity->nameId)) {
            throw new RuntimeException('Cannot forget an identity that is currently accredited as an SRAA');
        }

        if ($this->raListingRepository->contains(new IdentityId($identity->id))) {
            throw new RuntimeException('Cannot forget an identity that is currently accredited as an RA(A)');
        }
    }
}
