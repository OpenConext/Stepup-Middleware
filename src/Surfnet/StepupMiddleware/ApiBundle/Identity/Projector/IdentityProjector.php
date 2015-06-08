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

namespace Surfnet\StepupMiddleware\ApiBundle\Identity\Projector;

use Broadway\ReadModel\Projector;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\IdentityEmailChangedEvent;
use Surfnet\Stepup\Identity\Event\IdentityRenamedEvent;
use Surfnet\Stepup\Identity\Event\LocalePreferenceExpressedEvent;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;

class IdentityProjector extends Projector
{
    /**
     * @var IdentityRepository
     */
    private $identityRepository;

    public function __construct(IdentityRepository $identityRepository)
    {
        $this->identityRepository = $identityRepository;
    }

    public function applyIdentityCreatedEvent(IdentityCreatedEvent $event)
    {
        $this->identityRepository->save(Identity::create(
            (string) $event->identityId,
            $event->identityInstitution,
            $event->nameId,
            $event->email,
            $event->commonName,
            $event->preferredLocale
        ));
    }

    public function applyIdentityRenamedEvent(IdentityRenamedEvent $event)
    {
        $identity = $this->identityRepository->find((string) $event->identityId);
        $identity->commonName = $event->commonName;

        $this->identityRepository->save($identity);
    }

    public function applyIdentityEmailChangedEvent(IdentityEmailChangedEvent $event)
    {
        $identity = $this->identityRepository->find((string) $event->identityId);
        $identity->email = $event->email;

        $this->identityRepository->save($identity);
    }

    public function applyLocalePreferenceExpressedEvent(LocalePreferenceExpressedEvent $event)
    {
        $identity = $this->identityRepository->find((string) $event->identityId);
        $identity->preferredLocale = $event->preferredLocale;

        $this->identityRepository->save($identity);
    }
}
