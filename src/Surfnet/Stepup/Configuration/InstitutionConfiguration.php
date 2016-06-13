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
use Surfnet\Stepup\Configuration\Value\ContactInformation;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;
use Surfnet\Stepup\Configuration\Value\Location;
use Surfnet\Stepup\Configuration\Value\RaLocationId;
use Surfnet\Stepup\Configuration\Value\RaLocationList;
use Surfnet\Stepup\Configuration\Value\RaLocationName;
use Surfnet\Stepup\Exception\DomainException;

class InstitutionConfiguration extends EventSourcedAggregateRoot implements InstitutionConfigurationInterface
{
    /**
     * @var InstitutionConfigurationId
     */
    private $institutionConfigurationId;

    /**
     * @var RaLocationList
     */
    private $raLocations;

    /**
     * @param InstitutionConfigurationId $institutionConfigurationId
     * @param Institution $institution
     * @return InstitutionConfiguration
     */
    public static function create(InstitutionConfigurationId $institutionConfigurationId, Institution $institution)
    {
        // new self
        // apply institution configuration created event
    }

    final private function __construct()
    {
    }

    public function addRaLocation(
        RaLocationId $raLocationId,
        RaLocationName $raLocationName,
        Location $location,
        ContactInformation $contactInformation
    ) {
        if ($this->raLocations->hasWith($raLocationId)) {
            throw new DomainException(sprintf(
                'Cannot add RaLocation with RaLocationId "%s" to RaLocations of InstitutionConfiguration "%s":'
                . 'it is already present',
                $raLocationId,
                $this->getAggregateRootId()
            ));
        }

        // apply ra location added event
    }

    public function changeRaLocation(
        RaLocationId $raLocationId,
        RaLocationName $raLocationName,
        Location $location,
        ContactInformation $contactInformation
    ) {
        if (!$this->raLocations->hasWith($raLocationId)) {
            throw new DomainException(sprintf(
                'Cannot change RaLocation with RaLocationId "%s" in RaLocations of InstitutionConfiguration "%s":'
                . 'it is not present',
                $raLocationId,
                $this->getAggregateRootId()
            ));
        }

        $raLocation = $this->raLocations->getBy($raLocationId);

        if (!$raLocation->getLocationName()->equals($raLocationName)) {
            // apply renamed event
        }
        if (!$raLocation->getLocation()->equals($location)) {
            // apply relocated event
        }
        if (!$raLocation->getContactInformation()->equals($contactInformation)) {
            // apply contact information changed event
        }
    }

    public function removeRaLocation(RaLocationId $raLocationId)
    {
        if (!$this->raLocations->hasWith($raLocationId)) {
            throw new DomainException(sprintf(
                'Cannot remove RaLocation with RaLocationId "%s" in RaLocations of InstitutionConfiguration "%s":'
                . 'it is not present',
                $raLocationId,
                $this->getAggregateRootId()
            ));
        }

        // apply ra location removed event
    }

    public function getAggregateRootId()
    {
        return $this->institutionConfigurationId;
    }

    public function applyInstitutionConfigurationCreatedEvent()
    {
        // Implement
    }

    public function applyRaLocationAddedEvent()
    {
        // Implement
    }

    public function applyRaLocationRenamedEvent()
    {
        // Implement
    }

    public function applyRaLocationRelocatedEvent()
    {
        // Implement
    }

    public function applyRaLocationContactInformationChangedEvent()
    {
        // Implement
    }

    public function applyRaLocationRemovedEvent()
    {
        // Implement
    }
}
