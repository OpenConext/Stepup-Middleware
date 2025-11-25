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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\Configuration\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Broadway\EventHandling\EventBus as EventBusInterface;
use Broadway\EventSourcing\AggregateFactory\PublicConstructorAggregateFactory;
use Broadway\EventStore\EventStore as EventStoreInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Surfnet\Stepup\Configuration\Configuration;
use Surfnet\Stepup\Configuration\Event\ConfigurationUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\EmailTemplatesUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\IdentityProvidersUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\NewConfigurationCreatedEvent;
use Surfnet\Stepup\Configuration\Event\ServiceProvidersUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\SraaUpdatedEvent;
use Surfnet\Stepup\Configuration\EventSourcing\ConfigurationRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\UpdateConfigurationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\CommandHandler\ConfigurationCommandHandler;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\CommandHandlerTestBase;
use function is_string;

final class ConfigurationCommandHandlerTest extends CommandHandlerTestBase
{

    /**
     * Shorthand for fixed Configuration ID.
     */
    public const CID = Configuration::CONFIGURATION_ID;

    #[Test]
    #[Group('command-handler')]
    public function configuration_can_be_initialised(): void
    {
        $configuration = [
            'gateway' => [
                'identity_providers' => [],
                'service_providers' => [],
            ],
            'sraa' => [],
            'email_templates' => [
                'confirm_email' => ['en_GB' => ''],
                'registration_code' => ['en_GB' => ''],
            ],
        ];

        $this->scenario
            ->withAggregateId(self::CID)
            ->given([$this->createNewConfigurationCreatedEvent()])
            ->when($this->createUpdateCommand($configuration))
            ->then($this->createConfigurationUpdatedEvents($configuration, null));
    }

    #[Test]
    #[Group('command-handler')]
    public function configuration_can_be_updated(): void
    {
        $configuration1 = [
            'gateway' => [
                'identity_providers' => [],
                'service_providers' => [],
            ],
            'sraa' => [],
            'email_templates' => [
                'confirm_email' => ['en_GB' => ''],
                'registration_code' => ['en_GB' => ''],
            ],
        ];

        $configuration2 = [
            'gateway' => [
                'identity_providers' => [
                    [
                        "entity_id" => "https://entity.tld/id",
                        "loa" => [
                            "__default__" => "https://entity.tld/authentication/loa2",
                        ],
                    ],
                ],
                'service_providers' => [
                    [
                        "entity_id" => "https://entity.tld/id",
                        "public_key" => "MIIE...",
                        "acs" => ["https://entity.tld/consume-assertion"],
                        "loa" => [
                            "__default__" => "https://entity.tld/authentication/loa2",
                        ],
                        "assertion_encryption_enabled" => false,
                        "blacklisted_encryption_algorithms" => [],
                    ],
                ],
            ],
            'sraa' => [
                'SURFnet bv' => [
                    [
                        'name_id' => 'ddfd',
                    ],
                ],
            ],
            'email_templates' => [
                'confirm_email' => ['en_GB' => 'Verify {{ commonName }}'],
                'registration_code_with_ras' => ['en_GB' => 'Code {{ commonName }}'],
                'registration_code_with_ra_locations' => ['en_GB' => 'Code {{ commonName }}'],
            ],
        ];

        $this->scenario
            ->withAggregateId(self::CID)
            ->given(
                array_merge(
                    [$this->createNewConfigurationCreatedEvent()],
                    $this->createConfigurationUpdatedEvents($configuration1, null),
                ),
            )
            ->when($this->createUpdateCommand($configuration2))
            ->then($this->createConfigurationUpdatedEvents($configuration2, $configuration1));
    }

    protected function createCommandHandler(
        EventStoreInterface $eventStore,
        EventBusInterface $eventBus,
    ): CommandHandler {
        $aggregateFactory = new PublicConstructorAggregateFactory();

        return new ConfigurationCommandHandler(
            new ConfigurationRepository($eventStore, $eventBus, $aggregateFactory),
        );
    }

    private function createUpdateCommand(array $configuration): UpdateConfigurationCommand
    {
        $encodedConfiguration = json_encode($configuration);
        if (!is_string($encodedConfiguration)) {
            throw new RuntimeException('The configuration could not be json_encoded');
        }
        $configuration = new UpdateConfigurationCommand();
        $configuration->configuration = $encodedConfiguration;
        return $configuration;
    }

    /**
     * @return NewConfigurationCreatedEvent
     */
    private function createNewConfigurationCreatedEvent(): NewConfigurationCreatedEvent
    {
        return new NewConfigurationCreatedEvent(self::CID);
    }

    /**
     * @return array
     */
    private function createConfigurationUpdatedEvents(array $newConfiguration, array $oldConfiguration = null): array
    {
        return [
            new ConfigurationUpdatedEvent(self::CID, $newConfiguration, $oldConfiguration),
            new ServiceProvidersUpdatedEvent(self::CID, $newConfiguration['gateway']['service_providers']),
            new IdentityProvidersUpdatedEvent(self::CID, $newConfiguration['gateway']['identity_providers']),
            new SraaUpdatedEvent(self::CID, $newConfiguration['sraa']),
            new EmailTemplatesUpdatedEvent(self::CID, $newConfiguration['email_templates']),
        ];
    }
}
