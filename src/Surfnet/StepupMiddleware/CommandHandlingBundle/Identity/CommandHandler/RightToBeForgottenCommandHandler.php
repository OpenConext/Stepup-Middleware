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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Surfnet\Stepup\Identity\Api\Identity;
use Surfnet\Stepup\Identity\EventSourcing\IdentityRepository;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository as ApiIdentityRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ForgetIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Service\SensitiveDataService;

final class RightToBeForgottenCommandHandler extends CommandHandler
{
    /**
     * @var \Surfnet\Stepup\Identity\EventSourcing\IdentityRepository
     */
    private $repository;

    /**
     * @var \Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository
     */
    private $apiIdentityRepository;

    /**
     * @var \Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Service\SensitiveDataService
     */
    private $sensitiveDataService;

    /**
     * @param IdentityRepository    $repository
     * @param ApiIdentityRepository $apiIdentityRepository
     * @param SensitiveDataService  $sensitiveDataService
     */
    public function __construct(
        IdentityRepository $repository,
        ApiIdentityRepository $apiIdentityRepository,
        SensitiveDataService $sensitiveDataService
    ) {
        $this->repository = $repository;
        $this->apiIdentityRepository = $apiIdentityRepository;
        $this->sensitiveDataService = $sensitiveDataService;
    }

    public function handleForgetIdentityCommand(ForgetIdentityCommand $command)
    {
        $apiIdentity = $this->apiIdentityRepository->findOneByNameIdAndInstitution(
            new NameId($command->nameId),
            new Institution($command->institution)
        );
        $identityId = new IdentityId($apiIdentity->id);

        /** @var Identity $identity */
        $identity = $this->repository->load($identityId);
        $identity->forget();

        $this->repository->save($identity);
        $this->sensitiveDataService->forgetSensitiveData($identityId);
    }
}
