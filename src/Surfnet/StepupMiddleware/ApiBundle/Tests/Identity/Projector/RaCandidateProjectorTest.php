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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Identity\Projector;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventSourcing\Event;
use Broadway\ReadModel\Projector;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Rhumsaa\Uuid\Uuid;
use Surfnet\Stepup\Configuration\Event\SelectRaaOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\UseRaaOptionChangedEvent;
use Surfnet\Stepup\Configuration\Value\Institution as ConfigurationInstitution;
use Surfnet\Stepup\Configuration\Value\InstitutionAuthorizationOption;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution as ValueInstitution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaCandidate;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Projector\RaCandidateProjector;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaCandidateRepository;

final class RaCandidateProjectorTest extends WebTestCase
{
    /**
     * @var RaCandidateProjector
     */
    private $projector;

    /**
     * @var RaCandidateRepository
     */
    private $raCandidateRepository;

    /**
     * @var IdentityRepository
     */
    private $identityRepository;

    public function setUp()
    {
        parent::setUp();

        $this->loadFixtures([]);

        $this->raCandidateRepository = $this->getContainer()
            ->get('surfnet_stepup_middleware_api.repository.ra_candidate');

        $this->projector = $this->getContainer()
            ->get('surfnet_stepup_middleware_api.projector.ra_candidate');

        $this->identityRepository = $this->getContainer()
            ->get('surfnet_stepup_middleware_api.repository.identity');
    }

    /**
     * @test
     * @group api-projector
     */
    public function it_should_handle_events_correctly()
    {
        // Add identity
        $identity = Identity::create(
            'identity-id',
            new ValueInstitution('institution'),
            new NameId('name-id'),
            new Email('email@institution.com'),
            new CommonName('common-name'),
            new Locale('nl')
        );
        $this->identityRepository->save($identity);

        // Add two candidates
        $candidate = RaCandidate::nominate(
            new IdentityId('identity-id'),
            new ValueInstitution('institution'),
            new NameId('name-id'),
            new CommonName('common-name'),
            new Email('email@institution.com'),
            new ValueInstitution('institution-b')
        );
        $this->raCandidateRepository->merge($candidate);

        $candidate = RaCandidate::nominate(
            new IdentityId('identity-id'),
            new ValueInstitution('institution'),
            new NameId('name-id'),
            new CommonName('common-name'),
            new Email('email@institution.com'),
            new ValueInstitution('institution')
        );
        $this->raCandidateRepository->merge($candidate);

        // Update Raa option changed event
        $events = [
            new SelectRaaOptionChangedEvent(
                new InstitutionConfigurationId(Uuid::uuid4()->toString()),
                new ConfigurationInstitution('institution'),
                InstitutionAuthorizationOption::fromInstitutions(
                    InstitutionRole::selectRaa(),
                    new ConfigurationInstitution('institution'),
                    [
                        new ConfigurationInstitution('institution'),
                        new ConfigurationInstitution('institution-b'),
                    ]
                )
            ),
            new SelectRaaOptionChangedEvent(
                new InstitutionConfigurationId(Uuid::uuid4()->toString()),
                new ConfigurationInstitution('institution'),
                InstitutionAuthorizationOption::fromInstitutions(
                    InstitutionRole::selectRaa(),
                    new ConfigurationInstitution('institution'),
                    [
                        new ConfigurationInstitution('institution'),
                        new ConfigurationInstitution('institution-b'),
                    ]
                )
            )
        ];

        $this->applyEventsToProjector($this->projector, $events);

        $data = [];
        $raCandidates = $this->raCandidateRepository->findAll();
        foreach ($raCandidates as $raCandidate) {
            $data[] = json_encode($raCandidate, JSON_PRETTY_PRINT);
        }

        $this->assertEquals(
            [
                '{
    "identity_id": "identity-id",
    "institution": "institution",
    "common_name": "common-name",
    "email": "email@institution.com",
    "name_id": "name-id",
    "ra_institution": "institution-b"
}',
                '{
    "identity_id": "identity-id",
    "institution": "institution",
    "common_name": "common-name",
    "email": "email@institution.com",
    "name_id": "name-id",
    "ra_institution": "institution"
}',
            ],
            $data);
    }

    /**
     * @param Projector $projector
     * @param Event[] $events
     */
    private function applyEventsToProjector(Projector $projector, array $events)
    {
        foreach ($events as $event) {
            $domainMessage = $this->createDomainMessage($event);
            $projector->handle($domainMessage);
        }
    }

    private function createDomainMessage($event)
    {
        return DomainMessage::recordNow(1, 1, new Metadata(array()), $event);
    }

}
