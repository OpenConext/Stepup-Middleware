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

use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\Stepup\Projector\Projector;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\InstitutionListingRepository;

/**
 * @deprecated This could probably be removed and is only used in migrations
 * @see app/DoctrineMigrations/Version20160719090052.php#L51
 */
class InstitutionListingProjector extends Projector
{
    public function __construct(private readonly InstitutionListingRepository $institutionListingRepository)
    {
    }

    public function applyIdentityCreatedEvent(IdentityCreatedEvent $event): void
    {
        $this->institutionListingRepository->addIfNotExists($event->identityInstitution);
    }

    protected function applyIdentityForgottenEvent(IdentityForgottenEvent $event): void
    {
        // do nothing, no sensitive data in this projection
    }
}
