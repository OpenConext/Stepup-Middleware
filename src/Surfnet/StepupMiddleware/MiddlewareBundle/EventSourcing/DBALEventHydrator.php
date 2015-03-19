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
use Broadway\Serializer\SerializerInterface;
use Doctrine\DBAL\Connection;
use PDO;

class DBALEventHydrator
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var \Broadway\Serializer\SerializerInterface
     */
    private $payloadSerializer;

    /**
     * @var \Broadway\Serializer\SerializerInterface
     */
    private $metadataSerializer;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var \Doctrine\DBAL\Driver\Statement
     */
    private $loadStatement = null;

    /**
     * @param Connection          $connection
     * @param SerializerInterface $payloadSerializer
     * @param SerializerInterface $metadataSerializer
     * @param string              $tableName
     */
    public function __construct(
        Connection $connection,
        SerializerInterface $payloadSerializer,
        SerializerInterface $metadataSerializer,
        $tableName
    ) {
        $this->connection         = $connection;
        $this->payloadSerializer  = $payloadSerializer;
        $this->metadataSerializer = $metadataSerializer;
        $this->tableName          = $tableName;
    }

    /**
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getCount()
    {
        $statement = $this->connection->prepare('SELECT COUNT(1) AS cnt FROM ' . $this->tableName);
        $statement->execute();

        $row = $statement->fetch();

        return $row['cnt'];
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return DomainEventStream
     */
    public function getFromTill($limit, $offset)
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

    private function deserializeEvent($row)
    {
        return new DomainMessage(
            $row['uuid'],
            $row['playhead'],
            $this->metadataSerializer->deserialize(json_decode($row['metadata'], true)),
            $this->payloadSerializer->deserialize(json_decode($row['payload'], true)),
            DateTime::fromString($row['recorded_on'])
        );
    }

    private function prepareLoadStatement()
    {
        if ($this->loadStatement === null) {
            $query = 'SELECT uuid, playhead, metadata, payload, recorded_on
                FROM ' . $this->tableName . '
                ORDER BY recorded_on ASC
                LIMIT :limit OFFSET :offset';

            $this->loadStatement = $this->connection->prepare($query);
        }

        return $this->loadStatement;
    }
}
