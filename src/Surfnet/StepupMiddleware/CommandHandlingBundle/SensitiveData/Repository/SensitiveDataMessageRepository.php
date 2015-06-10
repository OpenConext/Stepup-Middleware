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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Repository;

use Doctrine\DBAL\Connection;
use Exception as CoreException;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\RuntimeException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\EventSourcing\SensitiveDataMessage;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\EventSourcing\SensitiveDataMessageStream;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\SensitiveData;

class SensitiveDataMessageRepository
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Finds all sensitive data records for a given Identity, ordered by playhead.
     *
     * @param IdentityId $identityId
     * @return SensitiveDataMessageStream
     */
    public function findByIdentityId(IdentityId $identityId)
    {
        $sql = 'SELECT identity_id, playhead, sensitive_data
                FROM event_stream_sensitive_data
                WHERE identity_id = :identity_id
                ORDER BY playhead ASC';

        $rows = $this->connection->fetchAll($sql, ['identity_id' => (string) $identityId]);
        $messages = array_map(function (array $row) use ($identityId) {
            return new SensitiveDataMessage(
                $identityId,
                (int) $row['playhead'],
                SensitiveData::deserialize(json_decode($row['sensitive_data'], true))
            );
        }, $rows);

        return new SensitiveDataMessageStream($messages);
    }

    /**
     * @param SensitiveDataMessageStream $sensitiveDataMessageStream
     * @return void
     */
    public function append(SensitiveDataMessageStream $sensitiveDataMessageStream)
    {
        $this->connection->beginTransaction();

        try {
            foreach ($sensitiveDataMessageStream as $sensitiveDataMessage) {
                /** @var SensitiveDataMessage $sensitiveDataMessage */
                $this->connection->insert('event_stream_sensitive_data', [
                    'identity_id'    => (string) $sensitiveDataMessage->getIdentityId(),
                    'playhead'       => $sensitiveDataMessage->getPlayhead(),
                    'sensitive_data' => json_encode($sensitiveDataMessage->getSensitiveData()->serialize()),
                ]);
            }
            $this->connection->commit();
        } catch (CoreException $e) {
            $this->connection->rollBack();
            throw new RuntimeException('An exception occurred while appending sensitive data', 0, $e);
        }
    }

    /**
     * @param SensitiveDataMessageStream $sensitiveDataMessageStream
     * @return void
     */
    public function update(SensitiveDataMessageStream $sensitiveDataMessageStream)
    {
        $this->connection->beginTransaction();

        try {
            foreach ($sensitiveDataMessageStream as $sensitiveDataMessage) {
                /** @var SensitiveDataMessage $sensitiveDataMessage */
                $this->connection->update(
                    'event_stream_sensitive_data',
                    ['sensitive_data' => json_encode($sensitiveDataMessage->getSensitiveData()->serialize())],
                    [
                        'identity_id' => (string) $sensitiveDataMessage->getIdentityId(),
                        'playhead'    => $sensitiveDataMessage->getPlayhead(),
                    ]
                );
            }
            $this->connection->commit();
        } catch (CoreException $e) {
            $this->connection->rollBack();
            throw new RuntimeException('An exception occurred while updating sensitive data', 0, $e);
        }
    }
}
