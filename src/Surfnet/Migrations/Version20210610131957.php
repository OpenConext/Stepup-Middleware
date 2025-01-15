<?php

/**
 * Copyright 2021 SURFnet bv
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

declare(strict_types=1);

namespace Surfnet\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * This migration removes sensitive data (vetting type) from the event stream
 * This data ended up by accident, this migration loads the targeted events and
 * removes the sensitive data via JSON encoding/decoding the payload of the event.
 */
final class Version20210610131957 extends AbstractMigration
{
    private static string $select = <<<SQL
        SELECT uuid, playhead, payload
        FROM event_stream
        WHERE (type = 'Surfnet.Stepup.Identity.Event.SecondFactorVettedEvent'
            OR type = 'Surfnet.Stepup.Identity.Event.SecondFactorVettedWithoutTokenProofOfPossession')
        AND recorded_on > '2021-04-11'
        AND payload LIKE '%"vetting_type":%';
SQL;

    private static string $update = <<<SQL
        UPDATE event_stream
        SET payload = :payload
        WHERE uuid = :uuid
        AND playhead = :playhead;
SQL;

    public function up(Schema $schema): void
    {
        // Do not show warning on migrations.
        $this->addSql('# Updating entities.');

        $result = $this->connection->executeQuery(self::$select);

        $affectedEventStreamRows = $result->fetchAllAssociative();
        $this->write("<info>Affected records: {$result->rowCount()}</info>");

        if ($result->rowCount() === 0) {
            return;
        }

        foreach ($affectedEventStreamRows as $eventStream) {
            $rawPayload = $eventStream['payload'];
            $uuid = $eventStream['uuid'];
            $playhead = $eventStream['playhead'];

            $this->write("<info>Migating: {$uuid}#{$playhead}</info>");

            $payload = $this->stripSensitiveData($rawPayload);
            $this->connection->executeUpdate(
                self::$update,
                [
                    'payload' => $payload,
                    'uuid' => $uuid,
                    'playhead' => $playhead,
                ],
            );
        }
    }

    public function down(Schema $schema): void
    {
        // This migration can not be undone.
    }

    private function stripSensitiveData(string $rawPayload): string
    {
        $payload = json_decode($rawPayload, true);
        unset($payload['payload']['vetting_type']);
        return json_encode($payload);
    }
}
