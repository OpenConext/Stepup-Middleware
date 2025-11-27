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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\EventSourcing;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Serializer\SimpleInterfaceSerializer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Statement;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Forgettable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\SensitiveData;

class DBALEventHydrator
{
    private ?Statement $loadStatement = null;

    /**
     * @param string $eventStreamTableName
     * @param string $sensitiveDataTable
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly SimpleInterfaceSerializer $payloadSerializer,
        private readonly SimpleInterfaceSerializer $metadataSerializer,
        private $eventStreamTableName,
        private $sensitiveDataTable,
    ) {
    }

    public function getCount(): int
    {
        $statement = $this->connection->prepare('SELECT COUNT(1) AS cnt FROM ' . $this->eventStreamTableName);
        $result = $statement->executeQuery();

        $row = $result->fetchAssociative();

        if (!$row) {
            return 0;
        }

        return (int) $row['cnt'];
    }

    public function getFromTill(int $limit, int $offset): DomainEventStream
    {
        $statement = $this->prepareLoadStatement();
        $statement->bindValue('limit', $limit, ParameterType::INTEGER);
        $statement->bindValue('offset', $offset, ParameterType::INTEGER);

        $result = $statement->executeQuery();

        $events = [];

        while ($row = $result->fetchAssociative()) {
            $events[] = $this->deserializeEvent($row);
        }

        return new DomainEventStream($events);
    }

    /**
     * @param string[] $eventTypes
     * @throws Exception
     */
    public function fetchByEventTypes(array $eventTypes): DomainEventStream
    {
        $eventTypePlaceholders = implode(', ', array_fill(0, count($eventTypes), '?'));

        $query = str_replace(
            ['%es%', '%sd%'],
            [$this->eventStreamTableName, $this->sensitiveDataTable],
            "SELECT %es%.uuid, %es%.playhead, %es%.metadata, %es%.payload, %es%.recorded_on, %sd%.sensitive_data
                FROM %es%
                LEFT JOIN %sd%
                    ON %es%.uuid = %sd%.identity_id
                        AND %es%.playhead = %sd%.playhead
                WHERE %es%.type IN ($eventTypePlaceholders)
                ORDER BY recorded_on, playhead ASC",
        );

        $statement = $this->connection->prepare($query);
        foreach ($eventTypes as $position => $parameter) {
            $statement->bindValue($position + 1, $parameter);
        }
        $results = $statement->executeQuery();

        $events = [];
        foreach ($results->fetchAllAssociative() as $row) {
            $events[] = $this->deserializeEvent($row);
        }

        return new DomainEventStream($events);
    }

    private function deserializeEvent(array $row): DomainMessage
    {
        $event = $this->payloadSerializer->deserialize(json_decode((string)$row['payload'], true));

        if ($event instanceof Forgettable) {
            $event->setSensitiveData(SensitiveData::deserialize(json_decode((string)$row['sensitive_data'], true)));
        }

        return new DomainMessage(
            $row['uuid'],
            $row['playhead'],
            $this->metadataSerializer->deserialize(json_decode((string)$row['metadata'], true)),
            $event,
            DateTime::fromString($row['recorded_on']),
        );
    }

    private function prepareLoadStatement(): Statement
    {
        if (!$this->loadStatement instanceof Statement) {
            $query = str_replace(
                ['%es%', '%sd%'],
                [$this->eventStreamTableName, $this->sensitiveDataTable],
                'SELECT %es%.uuid, %es%.playhead, %es%.metadata, %es%.payload, %es%.recorded_on, %sd%.sensitive_data
                FROM %es%
                LEFT JOIN %sd%
                    ON %es%.uuid = %sd%.identity_id
                        AND %es%.playhead = %sd%.playhead
                ORDER BY recorded_on ASC
                LIMIT :limit OFFSET :offset',
            );

            $this->loadStatement = $this->connection->prepare($query);
        }

        return $this->loadStatement;
    }
}
