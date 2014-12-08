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

namespace Surfnet\Stepup\Configuration;

use Broadway\EventSourcing\EventSourcedAggregateRoot;
use GuzzleHttp;
use Surfnet\Stepup\Configuration\Api\Configuration as ConfigurationInterface;
use Surfnet\Stepup\Configuration\Event\ConfigurationUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\NewConfigurationCreatedEvent;
use Surfnet\Stepup\Configuration\Event\RaaUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\ServiceProvidersUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\SraaUpdatedEvent;

class Configuration extends EventSourcedAggregateRoot implements ConfigurationInterface
{
    /**
     * There can ever be only one configuration, so using a fixed UUIDv4
     */
    const CONFIGURATION_ID = '12345678-abcd-4321-abcd-123456789012';

    /**
     * @var string
     */
    private $configuration;

    /**
     * @var null|\Surfnet\Stepup\Configuration\Event\ServiceProvidersUpdatedEvent
     */
    private $lastServiceProvidersUpdatedEvent;

    /**
     * @var null|\Surfnet\Stepup\Configuration\Event\RaaUpdatedEvent
     */
    private $lastRaaUpdatedEvent;

    /**
     * @var null|\Surfnet\Stepup\Configuration\Event\SraaUpdatedEvent
     */
    private $lastSraaUpdatedEvent;

    public static function create()
    {
        $configuration = new self();
        $configuration->apply(new NewConfigurationCreatedEvent(self::CONFIGURATION_ID));

        return $configuration;
    }

    public function update($configurationAsJson)
    {
        $decodedConfiguration = GuzzleHttp\json_decode($configurationAsJson, true);

        $this->apply(new ConfigurationUpdatedEvent(
            self::CONFIGURATION_ID,
            $decodedConfiguration,
            $this->configuration
        ));

        $this->lastServiceProvidersUpdatedEvent = new ServiceProvidersUpdatedEvent(
            self::CONFIGURATION_ID,
            $decodedConfiguration['gateway']['service_providers']
        );

        $this->lastRaaUpdatedEvent = new RaaUpdatedEvent(
            self::CONFIGURATION_ID,
            $decodedConfiguration['raa']
        );

        $this->lastSraaUpdatedEvent = new SraaUpdatedEvent(
            self::CONFIGURATION_ID,
            $decodedConfiguration['sraa']
        );

        $this->apply($this->lastServiceProvidersUpdatedEvent);
        $this->apply($this->lastRaaUpdatedEvent);
        $this->apply($this->lastSraaUpdatedEvent);
    }

    public function getAggregateRootId()
    {
        return self::CONFIGURATION_ID;
    }

    public function applyConfigurationUpdatedEvent(ConfigurationUpdatedEvent $event)
    {
        $this->configuration = $event->newConfiguration;
    }

    public function getLastUncommittedServiceProvidersUpdatedEvent()
    {
        return $this->lastServiceProvidersUpdatedEvent;
    }

    public function getLastUncommittedRaaUpdatedEvent()
    {
        return $this->lastRaaUpdatedEvent;
    }

    public function getLastUncommittedSraaUpdatedEvent()
    {
        return $this->lastSraaUpdatedEvent;
    }

    /**
     * {@inheritDoc} Cleaning up the possible event, as the uncommittedEvents will be removed as well
     */
    public function getUncommittedEvents()
    {
        $this->lastServiceProvidersUpdatedEvent = null;
        $this->lastRaaUpdatedEvent = null;
        $this->lastSraaUpdatedEvent = null;

        return parent::getUncommittedEvents();
    }
}
