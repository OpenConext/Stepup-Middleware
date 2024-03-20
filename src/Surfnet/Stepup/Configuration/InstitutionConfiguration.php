<?php

/**
 * Copyright 2016 SURFnet B.V.
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

namespace Surfnet\Stepup\Configuration;

use Broadway\EventSourcing\EventSourcedAggregateRoot;
use Surfnet\Stepup\Configuration\Api\InstitutionConfiguration as InstitutionConfigurationInterface;
use Surfnet\Stepup\Configuration\Entity\RaLocation;
use Surfnet\Stepup\Configuration\Event\AllowedSecondFactorListUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\InstitutionConfigurationRemovedEvent;
use Surfnet\Stepup\Configuration\Event\NewInstitutionConfigurationCreatedEvent;
use Surfnet\Stepup\Configuration\Event\NumberOfTokensPerIdentityOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationAddedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationContactInformationChangedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRelocatedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRemovedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRenamedEvent;
use Surfnet\Stepup\Configuration\Event\SelectRaaOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\SelfAssertedTokensOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\SelfVetOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\ShowRaaContactInformationOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\SsoOn2faOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\UseRaaOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\UseRaLocationsOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\UseRaOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\VerifyEmailOptionChangedEvent;
use Surfnet\Stepup\Configuration\Value\AllowedSecondFactorList;
use Surfnet\Stepup\Configuration\Value\ContactInformation;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionAuthorizationOption;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\Stepup\Configuration\Value\Location;
use Surfnet\Stepup\Configuration\Value\NumberOfTokensPerIdentityOption;
use Surfnet\Stepup\Configuration\Value\RaLocationId;
use Surfnet\Stepup\Configuration\Value\RaLocationList;
use Surfnet\Stepup\Configuration\Value\RaLocationName;
use Surfnet\Stepup\Configuration\Value\SelfAssertedTokensOption;
use Surfnet\Stepup\Configuration\Value\SelfVetOption;
use Surfnet\Stepup\Configuration\Value\ShowRaaContactInformationOption;
use Surfnet\Stepup\Configuration\Value\SsoOn2faOption;
use Surfnet\Stepup\Configuration\Value\UseRaLocationsOption;
use Surfnet\Stepup\Configuration\Value\VerifyEmailOption;
use Surfnet\Stepup\Exception\DomainException;

/**
 * InstitutionConfiguration aggregate root
 *
 * Some things to know about this aggregate:
 *
 * 1. The aggregate is instantiated by InstitutionConfigurationCommandHandler by calling the
 *    handleReconfigureInstitutionConfigurationOptionsCommand method. It does so, not by using the projections to build
 *    the aggregate but by playing the events onto the aggregate.
 * 2. If one of the configuration options should be nullable, take a look at the applyUseRaOptionChangedEvent doc block
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) Events and value objects
 * @SuppressWarnings(PHPMD.TooManyMethods) AggregateRoot
 * @SuppressWarnings(PHPMD.TooManyPublicMethods) AggregateRoot
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity) AggregateRoot
 */
class InstitutionConfiguration extends EventSourcedAggregateRoot implements InstitutionConfigurationInterface
{
    /**
     * @var InstitutionConfigurationId
     */
    private InstitutionConfigurationId $institutionConfigurationId;

    /**
     * @var Institution
     */
    private Institution $institution;

    private ?RaLocationList $raLocations = null;

    /**
     * @var UseRaLocationsOption
     */
    private UseRaLocationsOption $useRaLocationsOption;

    /**
     * @var ShowRaaContactInformationOption
     */
    private ShowRaaContactInformationOption $showRaaContactInformationOption;

    /**
     * @var VerifyEmailOption
     */
    private VerifyEmailOption $verifyEmailOption;

    /**
     * @var NumberOfTokensPerIdentityOption
     */
    private NumberOfTokensPerIdentityOption $numberOfTokensPerIdentityOption;

    /**
     * @var SelfVetOption
     */
    private SelfVetOption $selfVetOption;

    /**
     * @var SsoOn2faOption
     */
    private SsoOn2faOption $ssoOn2faOption;

    /**
     * @var SelfAssertedTokensOption
     */
    private SelfAssertedTokensOption $selfAssertedTokensOption;

    /**
     * @var InstitutionAuthorizationOption
     */
    private InstitutionAuthorizationOption $useRaOption;

    /**
     * @var InstitutionAuthorizationOption
     */

    private InstitutionAuthorizationOption $useRaaOption;

    /**
     * @var InstitutionAuthorizationOption
     */
    private InstitutionAuthorizationOption $selectRaaOption;

    /**
     * @var AllowedSecondFactorList
     */
    private AllowedSecondFactorList $allowedSecondFactorList;

    private ?bool $isMarkedAsDestroyed = null;

    /**
     * @param InstitutionConfigurationId $institutionConfigurationId
     * @param Institution $institution
     * @return InstitutionConfiguration
     */
    public static function create(
        InstitutionConfigurationId $institutionConfigurationId,
        Institution $institution,
    ): self {
        $institutionConfiguration = new self;
        $institutionConfiguration->apply(
            new NewInstitutionConfigurationCreatedEvent(
                $institutionConfigurationId,
                $institution,
                UseRaLocationsOption::getDefault(),
                ShowRaaContactInformationOption::getDefault(),
                VerifyEmailOption::getDefault(),
                NumberOfTokensPerIdentityOption::getDefault(),
                SsoOn2faOption::getDefault(),
                SelfVetOption::getDefault(),
                SelfAssertedTokensOption::getDefault(),
            ),
        );
        $institutionConfiguration->apply(
            new AllowedSecondFactorListUpdatedEvent(
                $institutionConfigurationId,
                $institution,
                AllowedSecondFactorList::blank(),
            ),
        );
        $institutionConfiguration->apply(
            new UseRaOptionChangedEvent(
                $institutionConfigurationId,
                $institution,
                InstitutionAuthorizationOption::getDefault(InstitutionRole::useRa()),
            ),
        );
        $institutionConfiguration->apply(
            new UseRaaOptionChangedEvent(
                $institutionConfigurationId,
                $institution,
                InstitutionAuthorizationOption::getDefault(InstitutionRole::useRaa()),
            ),
        );
        $institutionConfiguration->apply(
            new SelectRaaOptionChangedEvent(
                $institutionConfigurationId,
                $institution,
                InstitutionAuthorizationOption::getDefault(InstitutionRole::selectRaa()),
            ),
        );

        return $institutionConfiguration;
    }

    public function rebuild(): self
    {
        // We can only rebuild a destroyed InstitutionConfiguration, all other cases are not valid
        if ($this->isMarkedAsDestroyed !== true) {
            throw new DomainException('Cannot rebuild InstitutionConfiguration as it has not been destroyed');
        }

        $this->apply(
            new NewInstitutionConfigurationCreatedEvent(
                $this->institutionConfigurationId,
                $this->institution,
                UseRaLocationsOption::getDefault(),
                ShowRaaContactInformationOption::getDefault(),
                VerifyEmailOption::getDefault(),
                NumberOfTokensPerIdentityOption::getDefault(),
                SsoOn2faOption::getDefault(),
                SelfVetOption::getDefault(),
                SelfAssertedTokensOption::getDefault(),
            ),
        );
        $this->apply(
            new AllowedSecondFactorListUpdatedEvent(
                $this->institutionConfigurationId,
                $this->institution,
                AllowedSecondFactorList::blank(),
            ),
        );
        $this->apply(
            new UseRaOptionChangedEvent(
                $this->institutionConfigurationId,
                $this->institution,
                InstitutionAuthorizationOption::getDefault(InstitutionRole::useRa()),
            ),
        );
        $this->apply(
            new UseRaaOptionChangedEvent(
                $this->institutionConfigurationId,
                $this->institution,
                InstitutionAuthorizationOption::getDefault(InstitutionRole::useRaa()),
            ),
        );
        $this->apply(
            new SelectRaaOptionChangedEvent(
                $this->institutionConfigurationId,
                $this->institution,
                InstitutionAuthorizationOption::getDefault(InstitutionRole::selectRaa()),
            ),
        );

        return $this;
    }

    final public function __construct()
    {
    }

    public function configureUseRaLocationsOption(UseRaLocationsOption $useRaLocationsOption): void
    {
        if ($this->useRaLocationsOption->equals($useRaLocationsOption)) {
            return;
        }

        $this->apply(
            new UseRaLocationsOptionChangedEvent(
                $this->institutionConfigurationId,
                $this->institution,
                $useRaLocationsOption,
            ),
        );
    }

    public function configureShowRaaContactInformationOption(
        ShowRaaContactInformationOption $showRaaContactInformationOption,
    ): void {
        if ($this->showRaaContactInformationOption->equals($showRaaContactInformationOption)) {
            return;
        }

        $this->apply(
            new ShowRaaContactInformationOptionChangedEvent(
                $this->institutionConfigurationId,
                $this->institution,
                $showRaaContactInformationOption,
            ),
        );
    }

    public function configureVerifyEmailOption(VerifyEmailOption $verifyEmailOption): void
    {
        if ($this->verifyEmailOption->equals($verifyEmailOption)) {
            return;
        }

        $this->apply(
            new VerifyEmailOptionChangedEvent(
                $this->institutionConfigurationId,
                $this->institution,
                $verifyEmailOption,
            ),
        );
    }

    public function configureNumberOfTokensPerIdentityOption(
        NumberOfTokensPerIdentityOption $numberOfTokensPerIdentityOption,
    ): void {
        if ($this->numberOfTokensPerIdentityOption->equals($numberOfTokensPerIdentityOption)) {
            return;
        }

        $this->apply(
            new NumberOfTokensPerIdentityOptionChangedEvent(
                $this->institutionConfigurationId,
                $this->institution,
                $numberOfTokensPerIdentityOption,
            ),
        );
    }

    public function configureSelfVetOption(SelfVetOption $selfVetOption): void
    {
        if ($this->selfVetOption->equals($selfVetOption)) {
            return;
        }

        $this->apply(
            new SelfVetOptionChangedEvent(
                $this->institutionConfigurationId,
                $this->institution,
                $selfVetOption,
            ),
        );
    }

    public function configureSelfAssertedTokensOption(SelfAssertedTokensOption $selfAssertedTokensOption): void
    {
        if ($this->selfAssertedTokensOption !== null &&
            $this->selfAssertedTokensOption->equals($selfAssertedTokensOption)
        ) {
            return;
        }

        $this->apply(
            new SelfAssertedTokensOptionChangedEvent(
                $this->institutionConfigurationId,
                $this->institution,
                $selfAssertedTokensOption,
            ),
        );
    }

    public function configureSsoOn2faOption(SsoOn2faOption $ssoOn2faOption): void
    {
        if ($this->ssoOn2faOption !== null && $this->ssoOn2faOption->equals($ssoOn2faOption)) {
            return;
        }

        $this->apply(
            new SsoOn2faOptionChangedEvent(
                $this->institutionConfigurationId,
                $this->institution,
                $ssoOn2faOption,
            ),
        );
    }

    public function updateUseRaOption(InstitutionAuthorizationOption $useRaOption): void
    {
        if ($this->useRaOption !== null
            && $this->useRaOption->equals($useRaOption)
        ) {
            return;
        }

        $this->apply(
            new UseRaOptionChangedEvent(
                $this->institutionConfigurationId,
                $this->institution,
                $useRaOption,
            ),
        );
    }

    public function updateUseRaaOption(InstitutionAuthorizationOption $useRaaOption): void
    {
        if ($this->useRaaOption !== null
            && $this->useRaaOption->equals($useRaaOption)
        ) {
            return;
        }

        $this->apply(
            new UseRaaOptionChangedEvent(
                $this->institutionConfigurationId,
                $this->institution,
                $useRaaOption,
            ),
        );
    }

    public function updateSelectRaaOption(InstitutionAuthorizationOption $selectRaaOption): void
    {
        if ($this->selectRaaOption !== null
            && $this->selectRaaOption->equals($selectRaaOption)
        ) {
            return;
        }

        $this->apply(
            new SelectRaaOptionChangedEvent(
                $this->institutionConfigurationId,
                $this->institution,
                $selectRaaOption,
            ),
        );
    }

    public function updateAllowedSecondFactorList(AllowedSecondFactorList $allowedSecondFactorList): void
    {
        // AllowedSecondFactorList can be null for InstitutionConfigurations for which this functionality did not exist
        if ($this->allowedSecondFactorList !== null
            && $this->allowedSecondFactorList->equals($allowedSecondFactorList)
        ) {
            return;
        }

        $this->apply(
            new AllowedSecondFactorListUpdatedEvent(
                $this->institutionConfigurationId,
                $this->institution,
                $allowedSecondFactorList,
            ),
        );
    }

    /**
     * @param RaLocationId $raLocationId
     * @param RaLocationName $raLocationName
     * @param Location $location
     * @param ContactInformation $contactInformation
     */
    public function addRaLocation(
        RaLocationId $raLocationId,
        RaLocationName $raLocationName,
        Location $location,
        ContactInformation $contactInformation,
    ): void {
        if ($this->raLocations->containsWithId($raLocationId)) {
            throw new DomainException(
                sprintf(
                    'Cannot add RaLocation with RaLocationId "%s" to RaLocations of InstitutionConfiguration "%s":'
                    . ' it is already present',
                    $raLocationId,
                    $this->getAggregateRootId(),
                ),
            );
        }

        $this->apply(
            new RaLocationAddedEvent(
                $this->institutionConfigurationId,
                $this->institution,
                $raLocationId,
                $raLocationName,
                $location,
                $contactInformation,
            ),
        );
    }

    /**
     * @param RaLocationId $raLocationId
     * @param RaLocationName $raLocationName
     * @param Location $location
     * @param ContactInformation $contactInformation
     */
    public function changeRaLocation(
        RaLocationId $raLocationId,
        RaLocationName $raLocationName,
        Location $location,
        ContactInformation $contactInformation,
    ): void {
        if (!$this->raLocations->containsWithId($raLocationId)) {
            throw new DomainException(
                sprintf(
                    'Cannot change RaLocation with RaLocationId "%s" in RaLocations of InstitutionConfiguration "%s":'
                    . ' it is not present',
                    $raLocationId,
                    $this->getAggregateRootId(),
                ),
            );
        }

        $raLocation = $this->raLocations->getById($raLocationId);

        if (!$raLocation->getName()->equals($raLocationName)) {
            $this->apply(
                new RaLocationRenamedEvent($this->institutionConfigurationId, $raLocationId, $raLocationName),
            );
        }
        if (!$raLocation->getLocation()->equals($location)) {
            $this->apply(
                new RaLocationRelocatedEvent($this->institutionConfigurationId, $raLocationId, $location),
            );
        }
        if (!$raLocation->getContactInformation()->equals($contactInformation)) {
            $this->apply(
                new RaLocationContactInformationChangedEvent(
                    $this->institutionConfigurationId,
                    $raLocationId,
                    $contactInformation,
                ),
            );
        }
    }

    /**
     * @param RaLocationId $raLocationId
     */
    public function removeRaLocation(RaLocationId $raLocationId): void
    {
        if (!$this->raLocations->containsWithId($raLocationId)) {
            throw new DomainException(
                sprintf(
                    'Cannot remove RaLocation with RaLocationId "%s" in RaLocations of InstitutionConfiguration "%s":'
                    . ' it is not present',
                    $raLocationId,
                    $this->getAggregateRootId(),
                ),
            );
        }

        $this->apply(new RaLocationRemovedEvent($this->institutionConfigurationId, $raLocationId));
    }

    /**
     * @return void
     */
    public function destroy(): void
    {
        $this->apply(new InstitutionConfigurationRemovedEvent($this->institutionConfigurationId, $this->institution));
    }

    public function getAggregateRootId(): string
    {
        return $this->institutionConfigurationId;
    }

    /**
     * Check if role from institution is allowed to accredit roles
     *
     */
    public function isInstitutionAllowedToAccreditRoles(Institution $institution): bool
    {
        // This method is needed to support the situation pre FGA. In that situation the SelectRaaOptionChanged wasn't
        // fired and that would result in a situation were $this->selectRaaOption is null. If that occurs we should check
        // if the institution of the identity is the institution to validate.
        if ($this->selectRaaOption == null) {
            return $this->institution->equals($institution);
        }
        return $this->selectRaaOption->hasInstitution($institution, $this->institution);
    }

    protected function applyNewInstitutionConfigurationCreatedEvent(NewInstitutionConfigurationCreatedEvent $event): void
    {
        $this->institutionConfigurationId = $event->institutionConfigurationId;
        $this->institution = $event->institution;
        $this->useRaLocationsOption = $event->useRaLocationsOption;
        $this->showRaaContactInformationOption = $event->showRaaContactInformationOption;
        $this->verifyEmailOption = $event->verifyEmailOption;
        $this->selfVetOption = $event->selfVetOption;
        $this->ssoOn2faOption = $event->ssoOn2faOption;
        $this->selfAssertedTokensOption = $event->selfAssertedTokensOption;
        $this->numberOfTokensPerIdentityOption = $event->numberOfTokensPerIdentityOption;
        $this->raLocations = new RaLocationList([]);
        $this->isMarkedAsDestroyed = false;
    }

    /**
     * Apply the UseRaOptionChangedEvent
     *
     * To ensure the aggregate is correctly populated with the FGA options we ensure the UseRaOptionChangedEvent
     * can be applied on the aggregate. Refraining from doing this would result in the $this->useRaOption field only
     * being applied when applyNewInstitutionConfigurationCreatedEvent is called. And this might not be the case if
     * the fields where null'ed (removed from configuration).
     *
     * This also applies for applyUseRaaOptionChangedEvent & applySelectRaaOptionChangedEvent
     */
    protected function applyUseRaOptionChangedEvent(UseRaOptionChangedEvent $event): void
    {
        $this->useRaOption = $event->useRaOption;
    }

    protected function applyUseRaaOptionChangedEvent(UseRaaOptionChangedEvent $event): void
    {
        $this->useRaaOption = $event->useRaaOption;
    }

    protected function applySelectRaaOptionChangedEvent(SelectRaaOptionChangedEvent $event): void
    {
        $this->selectRaaOption = $event->selectRaaOption;
    }

    protected function applyUseRaLocationsOptionChangedEvent(UseRaLocationsOptionChangedEvent $event): void
    {
        $this->useRaLocationsOption = $event->useRaLocationsOption;
    }

    protected function applyShowRaaContactInformationOptionChangedEvent(
        ShowRaaContactInformationOptionChangedEvent $event,
    ): void
    {
        $this->showRaaContactInformationOption = $event->showRaaContactInformationOption;
    }

    protected function applyVerifyEmailOptionChangedEvent(
        VerifyEmailOptionChangedEvent $event,
    ): void
    {
        $this->verifyEmailOption = $event->verifyEmailOption;
    }

    protected function applySelfVetOptionChangedEvent(
        SelfVetOptionChangedEvent $event,
    ): void
    {
        $this->selfVetOption = $event->selfVetOption;
    }

    protected function applySelfAssertedTokensOptionChangedEvent(
        SelfAssertedTokensOptionChangedEvent $event,
    ): void
    {
        $this->selfAssertedTokensOption = $event->selfAssertedTokensOption;
    }

    protected function applySsoOn2faOptionChangedEvent(
        SsoOn2faOptionChangedEvent $event,
    ): void
    {
        $this->ssoOn2faOption = $event->ssoOn2faOption;
    }

    protected function applyNumberOfTokensPerIdentityOptionChangedEvent(
        NumberOfTokensPerIdentityOptionChangedEvent $event,
    ): void
    {
        $this->numberOfTokensPerIdentityOption = $event->numberOfTokensPerIdentityOption;
    }

    protected function applyAllowedSecondFactorListUpdatedEvent(AllowedSecondFactorListUpdatedEvent $event): void
    {
        $this->allowedSecondFactorList = $event->allowedSecondFactorList;
    }

    protected function applyRaLocationAddedEvent(RaLocationAddedEvent $event): void
    {
        $this->raLocations->add(
            RaLocation::create(
                $event->raLocationId,
                $event->raLocationName,
                $event->location,
                $event->contactInformation,
            ),
        );
    }

    protected function applyRaLocationRenamedEvent(RaLocationRenamedEvent $event): void
    {
        $raLocation = $this->raLocations->getById($event->raLocationId);
        $raLocation->rename($event->raLocationName);
    }

    protected function applyRaLocationRelocatedEvent(RaLocationRelocatedEvent $event): void
    {
        $raLocation = $this->raLocations->getById($event->raLocationId);
        $raLocation->relocate($event->location);
    }

    protected function applyRaLocationContactInformationChangedEvent(RaLocationContactInformationChangedEvent $event): void
    {
        $raLocation = $this->raLocations->getById($event->raLocationId);
        $raLocation->changeContactInformation($event->contactInformation);
    }

    protected function applyRaLocationRemovedEvent(RaLocationRemovedEvent $event): void
    {
        $this->raLocations->removeWithId($event->raLocationId);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function applyInstitutionConfigurationRemovedEvent(InstitutionConfigurationRemovedEvent $event): void
    {
        // reset all configuration to defaults. This way, should it be rebuild, it seems like it is new again
        $this->raLocations = new RaLocationList([]);
        $this->useRaLocationsOption = UseRaLocationsOption::getDefault();
        $this->showRaaContactInformationOption = ShowRaaContactInformationOption::getDefault();
        $this->verifyEmailOption = VerifyEmailOption::getDefault();
        $this->numberOfTokensPerIdentityOption = NumberOfTokensPerIdentityOption::getDefault();
        $this->allowedSecondFactorList = AllowedSecondFactorList::blank();
        $this->useRaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::useRa());
        $this->useRaaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::useRaa());
        $this->selectRaaOption = InstitutionAuthorizationOption::getDefault(InstitutionRole::selectRaa());

        $this->isMarkedAsDestroyed = true;
    }
}
