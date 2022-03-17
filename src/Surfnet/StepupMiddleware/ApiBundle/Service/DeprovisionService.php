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
use Surfnet\Stepup\Identity\EventSourcing\IdentityRepository;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\ApiBundle\Exception\UserNotFoundException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository as ApiIdentityRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline;

class DeprovisionService implements DeprovisionServiceInterface
{
    /**
     * @var Pipeline
     */
    private $pipeline;

    /**
     * @var IdentityRepository
     */
    private $eventSourcingRepository;

    /**
     * @var ApiIdentityRepository
     */
    private $apiRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Pipeline $pipeline
     * @param IdentityRepository $repository
     */
    public function __construct(
        Pipeline $pipeline,
        IdentityRepository $eventSourcingRepository,
        ApiIdentityRepository $apiRepository,
        LoggerInterface $logger
    ) {
        $this->pipeline = $pipeline;
        $this->eventSourcingRepository = $eventSourcingRepository;
        $this->apiRepository = $apiRepository;
        $this->logger = $logger;
    }

    public function readUserData(string $collabPersonId): array
    {
        try {
            $identityId = $this->getIdentityIdByNameId($collabPersonId);
            return $this->eventSourcingRepository->obtainInformation($identityId);
        } catch (UserNotFoundException $e) {
            $this->logger->notice(
                sprintf(
                    'User identified by: %s was not found. Unable to provide deprovision data.',
                    $collabPersonId
                )
            );
            return [];
        }
    }

    public function deprovision(string $collabPersonId)
    {
    }

    private function getIdentityIdByNameId(string $collabPersonId): IdentityId
    {
        $user = $this->apiRepository->findOneByNameId($collabPersonId);
        if (!$user) {
            throw new UserNotFoundException();
        }
        return new IdentityId($user->id);
    }
}
