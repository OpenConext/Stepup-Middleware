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
use Surfnet\Stepup\Configuration\Api\Configuration as ConfigurationInterface;
use Surfnet\Stepup\Configuration\Event\ConfigurationUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\EmailTemplatesUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\IdentityProvidersUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\NewConfigurationCreatedEvent;
use Surfnet\Stepup\Configuration\Event\ServiceProvidersUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\SraaUpdatedEvent;
use Surfnet\Stepup\Helper\JsonHelper;

class Configuration extends EventSourcedAggregateRoot implements ConfigurationInterface
{
    /**
     * There can ever be only one configuration, so using a fixed UUIDv4
     */
    public const CONFIGURATION_ID = '12345678-abcd-4321-abcd-123456789012';

    /**
     * @var array
     */
    private array|null $configuration = null;

    public static function create(): self
    {
        $configuration = new self();
        $configuration->apply(new NewConfigurationCreatedEvent(self::CONFIGURATION_ID));

        return $configuration;
    }

    public function update(string $newConfiguration): void
    {
        $decodedConfiguration = JsonHelper::decode($newConfiguration);

        $this->apply(
            new ConfigurationUpdatedEvent(
                self::CONFIGURATION_ID,
                $decodedConfiguration,
                $this->configuration,
            ),
        );

        $this->apply(
            new ServiceProvidersUpdatedEvent(
                self::CONFIGURATION_ID,
                $decodedConfiguration['gateway']['service_providers'],
            ),
        );
        $this->apply(
            new IdentityProvidersUpdatedEvent(
                self::CONFIGURATION_ID,
                $decodedConfiguration['gateway']['identity_providers'],
            ),
        );
        $this->apply(new SraaUpdatedEvent(self::CONFIGURATION_ID, $decodedConfiguration['sraa']));
        $this->apply(new EmailTemplatesUpdatedEvent(self::CONFIGURATION_ID, $decodedConfiguration['email_templates']));
    }

    public function getAggregateRootId(): string
    {
        return self::CONFIGURATION_ID;
    }

    public function applyConfigurationUpdatedEvent(ConfigurationUpdatedEvent $event): void
    {
        $this->configuration = $event->newConfiguration;
    }
}
