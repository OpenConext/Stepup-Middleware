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
use Surfnet\Stepup\Configuration\Event\InstitutionConfigurationRemovedEvent;
use Surfnet\Stepup\Configuration\Event\SelectRaaOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\SraaUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\UseRaaOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\UseRaOptionChangedEvent;
use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Configuration\Value\Institution as ConfigurationInstitution;
use Surfnet\Stepup\Identity\Event\CompliedWithVettedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaaEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaEvent;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityRetractedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\YubikeySecondFactorBootstrappedEvent;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\InstitutionAuthorizationRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaCandidate;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaCandidateRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaListingRepository;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RaCandidateProjector extends Projector
{
    /**
     * @var RaCandidateRepository
     */
    private $raCandidateRepository;

    /**
     * @var RaListingRepository
     */
    private $raListingRepository;

    /**
     * @var institutionAuthorizationRepository
     */
    private $institutionAuthorizationRepository;
    /**
     * @var IdentityRepository
     */
    private $identityRepository;

    public function __construct(
        RaCandidateRepository $raCandidateRepository,
        RaListingRepository $raListingRepository,
        InstitutionAuthorizationRepository $institutionAuthorizationRepository,
        IdentityRepository $identityRepository
    ) {
        $this->raCandidateRepository = $raCandidateRepository;
        $this->raListingRepository = $raListingRepository;
        $this->institutionAuthorizationRepository = $institutionAuthorizationRepository;
        $this->identityRepository = $identityRepository;
    }

    /**
     * @param SecondFactorVettedEvent $event
     * @return void
     */
    public function applySecondFactorVettedEvent(SecondFactorVettedEvent $event)
    {
        $institutionAuthorizations = $this->institutionAuthorizationRepository
            ->findAuthorizationOptionsForInstitution(new ConfigurationInstitution($event->identityInstitution));

        foreach ($institutionAuthorizations as $authorization) {

            $institution = new Institution($authorization->institution);

            if ($this->raListingRepository->findByIdentityIdAndInstitution($event->identityId, $institution)) {
                continue;
            }

            $candidate = RaCandidate::nominate(
                $event->identityId,
                $event->identityInstitution,
                $event->nameId,
                $event->commonName,
                $event->email,
                $institution
            );

            $this->raCandidateRepository->merge($candidate);
        }
    }

    /**
     * @param YubikeySecondFactorBootstrappedEvent $event
     * @return void
     */
    public function applyYubikeySecondFactorBootstrappedEvent(YubikeySecondFactorBootstrappedEvent $event)
    {
        $institutionAuthorizations = $this->institutionAuthorizationRepository
            ->findAuthorizationOptionsForInstitution(new ConfigurationInstitution($event->identityInstitution));

        foreach ($institutionAuthorizations as $authorization) {

            $institution = new Institution($authorization->institution);

            $candidate = RaCandidate::nominate(
                $event->identityId,
                $event->identityInstitution,
                $event->nameId,
                $event->commonName,
                $event->email,
                $institution
            );

            $this->raCandidateRepository->merge($candidate);
        }
    }

    /**
     * @param VettedSecondFactorRevokedEvent $event
     * @return void
     */
    public function applyVettedSecondFactorRevokedEvent(VettedSecondFactorRevokedEvent $event)
    {
        $this->raCandidateRepository->removeByIdentityId($event->identityId);
    }

    /**
     * @param CompliedWithVettedSecondFactorRevocationEvent $event
     * @return void
     */
    public function applyCompliedWithVettedSecondFactorRevocationEvent(
        CompliedWithVettedSecondFactorRevocationEvent $event
    ) {
        $this->raCandidateRepository->removeByIdentityId($event->identityId);
    }

    /**
     * @param SraaUpdatedEvent $event
     *
     * Removes all RaCandidates that have a nameId matching an SRAA, as they cannot be made RA(A) as they
     * already are SRAA.
     */
    public function applySraaUpdatedEvent(SraaUpdatedEvent $event)
    {
        $this->raCandidateRepository->removeByNameIds($event->sraaList);
    }

    /**
     * @param IdentityAccreditedAsRaEvent $event
     * @return void
     */
    public function applyIdentityAccreditedAsRaEvent(IdentityAccreditedAsRaEvent $event)
    {
        $this->raCandidateRepository->removeByIdentityIdAndRaInstitution($event->identityId, $event->raInstitution);
    }

    /**
     * @param IdentityAccreditedAsRaaEvent $event
     * @return void
     */
    public function applyIdentityAccreditedAsRaaEvent(IdentityAccreditedAsRaaEvent $event)
    {
        $this->raCandidateRepository->removeByIdentityIdAndRaInstitution($event->identityId, $event->raInstitution);
    }

    /**
     * @param RegistrationAuthorityRetractedEvent $event
     * @return void
     */
    public function applyRegistrationAuthorityRetractedEvent(RegistrationAuthorityRetractedEvent $event)
    {
        $candidate = RaCandidate::nominate(
            $event->identityId,
            $event->identityInstitution,
            $event->nameId,
            $event->commonName,
            $event->email,
            $event->raInstitution
        );

        $this->raCandidateRepository->merge($candidate);
    }

    protected function applyIdentityForgottenEvent(IdentityForgottenEvent $event)
    {
        $this->raCandidateRepository->removeByIdentityId($event->identityId);
    }

    protected function applyUseRaOptionChangedEvent(UseRaOptionChangedEvent $event)
    {
        $authorizedInstitutions = $event->useRaOption->getInstitutions($event->institution);
        $this->updateInstitutionCandidatesFromCollection(new Institution($event->institution->getInstitution()), $authorizedInstitutions);
    }

    protected function applyUseRaaOptionChangedEvent(UseRaaOptionChangedEvent $event)
    {
        $authorizedInstitutions = $event->useRaaOption->getInstitutions($event->institution);
        $this->updateInstitutionCandidatesFromCollection(new Institution($event->institution->getInstitution()), $authorizedInstitutions);
    }

    protected function applySelectRaaOptionChangedEvent(SelectRaaOptionChangedEvent $event)
    {
        $authorizedInstitutions = $event->selectRaaOption->getInstitutions($event->institution);
        $this->updateInstitutionCandidatesFromCollection(new Institution($event->institution->getInstitution()), $authorizedInstitutions);
    }

    protected function applyInstitutionConfigurationRemovedEvent(InstitutionConfigurationRemovedEvent $event)
    {
        $this->raCandidateRepository->removeByRaInstitution(new Institution($event->institution->getInstitution()));
    }

    /**
     * @param Institution $institution
     * @param ConfigurationInstitution[] $authorizedInstitutions
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function updateInstitutionCandidatesFromCollection(Institution $institution, array $authorizedInstitutions)
    {

        $raInstitutions = new InstitutionCollection();
        foreach ($authorizedInstitutions as $authorizedInstitution) {
            $raInstitutions->add(new Institution($authorizedInstitution->getInstitution()));
        }

        $this->raCandidateRepository->removeInstitutionsNotInList($institution, $raInstitutions);

        // loop through authorized institutions
        foreach ($raInstitutions as $raInstitution) {

            // add new identities
            $identities = $this->identityRepository->findByInstitution($raInstitution);
            foreach ($identities as $identity) {
                $identityId = new IdentityId($identity->id);

                // check if persistent in ra listing
                if ($this->raListingRepository->findByIdentityIdAndInstitution($identityId, $raInstitution)) {
                    continue;
                }

                // create candidate if not existing
                $candidate = $this->raCandidateRepository->findByIdentityId($identityId);
                if (!$candidate) {
                    $candidate = RaCandidate::nominate(
                        $identityId,
                        $identity->institution,
                        $identity->nameId,
                        $identity->commonName,
                        $identity->email,
                        $raInstitution
                    );
                }

                // store
                $this->raCandidateRepository->merge($candidate);
            }
        }
    }
}
