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
use Surfnet\Stepup\Identity\Identity;
use Surfnet\Stepup\Identity\EventSourcing\IdentityRepository;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\CreateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProveYubikeyPossessionCommand;

class IdentityCommandHandler extends CommandHandler
{
    /**
     * @var IdentityRepository
     */
    private $repository;

    /**
     * @param IdentityRepository $repository
     */
    public function __construct(IdentityRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handleCreateIdentityCommand(CreateIdentityCommand $command)
    {
        $identity = Identity::create(new IdentityId($command->id), new NameId($command->nameId));

        $this->repository->add($identity);
    }

    public function handleProveYubikeyPossessionCommand(ProveYubikeyPossessionCommand $command)
    {
        /** @var Identity $identity */
        $identity = $this->repository->load(new IdentityId($command->identityId));

        $identity->provePossessionOfYubikey(
            new SecondFactorId($command->secondFactorId),
            new YubikeyPublicId($command->yubikeyPublicId)
        );

        $this->repository->add($identity);
    }
}
