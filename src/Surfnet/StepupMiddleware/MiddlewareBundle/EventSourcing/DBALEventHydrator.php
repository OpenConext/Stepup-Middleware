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
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement;
use PDO;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Forgettable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\SensitiveData;

class DBALEventHydrator
{
    private Connection $connection;

    private SimpleInterfaceSerializer $payloadSerializer;

    private SimpleInterfaceSerializer $metadataSerializer;

    /**
     * @var string
     */
    private $eventStreamTableName;

    /**
     * @var string
     */
    private $sensitiveDataTable;

    /**
     * @var Statement
     */
    private $loadStatement = null;

    /**
     * @param Connection          $connection
     * @param SimpleInterfaceSerializer $payloadSerializer
     * @param SimpleInterfaceSerializer $metadataSerializer
     * @param string              $eventStreamTable
     * @param string              $sensitiveDataTable
     */
    public function __construct(
        Connection $connection,
        SimpleInterfaceSerializer $payloadSerializer,
        SimpleInterfaceSerializer $metadataSerializer,
        $eventStreamTable,
        $sensitiveDataTable
    ) {
        $this->connection         = $connection;
        $this->payloadSerializer  = $payloadSerializer;
        $this->metadataSerializer = $metadataSerializer;
        $this->eventStreamTableName = $eventStreamTable;
        $this->sensitiveDataTable = $sensitiveDataTable;
    }

    /**
     * @return string
     * @throws DBALException
     */
    public function getCount()
    {
        $statement = $this->connection->prepare('SELECT COUNT(1) AS cnt FROM ' . $this->eventStreamTableName);
        $statement->execute();

        $row = $statement->fetch();

        return $row['cnt'];
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return DomainEventStream
     */
    public function getFromTill($limit, $offset): DomainEventStream
    {
        $statement = $this->prepareLoadStatement();
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);

        $statement->execute();

        $events = array();
        while ($row = $statement->fetch()) {
            $events[] = $this->deserializeEvent($row);
        }

        return new DomainEventStream($events);
    }

    public function fetchByEventTypes($eventTypes): DomainEventStream
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
                ORDER BY recorded_on, playhead ASC"
        );

        $statement = $this->connection->prepare($query);
        $statement->execute($eventTypes);

        $events = array();
        while ($row = $statement->fetch()) {
            $events[] = $this->deserializeEvent($row);
        }

        return new DomainEventStream($events);
    }

    private function deserializeEvent(array $row): DomainMessage
    {
        $event = $this->payloadSerializer->deserialize(json_decode($row['payload'], true));

        if ($event instanceof Forgettable) {
            $event->setSensitiveData(SensitiveData::deserialize(json_decode($row['sensitive_data'], true)));
        }

        return new DomainMessage(
            $row['uuid'],
            $row['playhead'],
            $this->metadataSerializer->deserialize(json_decode($row['metadata'], true)),
            $event,
            DateTime::fromString($row['recorded_on'])
        );
    }

    private function prepareLoadStatement()
    {
        if ($this->loadStatement === null) {
            $query = str_replace(
                ['%es%', '%sd%'],
                [$this->eventStreamTableName, $this->sensitiveDataTable],
                'SELECT %es%.uuid, %es%.playhead, %es%.metadata, %es%.payload, %es%.recorded_on, %sd%.sensitive_data
                FROM %es%
                LEFT JOIN %sd%
                    ON %es%.uuid = %sd%.identity_id
                        AND %es%.playhead = %sd%.playhead
                ORDER BY recorded_on ASC
                LIMIT :limit OFFSET :offset'
            );

            $this->loadStatement = $this->connection->prepare($query);
        }

        return $this->loadStatement;
    }
}
