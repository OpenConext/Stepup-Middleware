<?php declare(strict_types=1);

namespace Surfnet\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use function json_decode;
use function json_encode;

/**
 * This migration removes sensitive data (vetting type) from the event stream
 * This data ended up by accident, this migration loads the targeted events and
 * removes the sensitive data via JSON encoding/decoding the payload of the event.
 */
final class Version20210610131957 extends AbstractMigration
{
    private static $select = <<<SQL
        SELECT uuid, playhead, payload
        FROM event_stream
        WHERE (type = 'Surfnet.Stepup.Identity.Event.SecondFactorVettedEvent'
            OR type = 'Surfnet.Stepup.Identity.Event.SecondFactorVettedWithoutTokenProofOfPossession')
        AND recorded_on > '2021-04-11'
        AND payload LIKE '%"vetting_type":%';
SQL;

    private static $update = <<<SQL
        UPDATE event_stream
        SET payload = '%s'
        WHERE uuid = '%s'
        AND playhead = %s;
SQL;

    public function up(Schema $schema) : void
    {
        // Do not show warning on migrations.
        $this->addSql('# Updating entities.');

        $affectedEventStreamRows = $this->connection->executeQuery(self::$select);
        if ($affectedEventStreamRows->rowCount() === 0) {
            return;
        }
        foreach ($affectedEventStreamRows as $eventStream) {
            $rawPayload = $eventStream['payload'];
            $uuid = $eventStream['uuid'];
            $playhead = $eventStream['playhead'];

            $payload = $this->stripSensitiveData($rawPayload);

            $this->connection->executeUpdate(
                sprintf(
                    self::$update,
                    $payload,
                    $uuid,
                    $playhead
                )
            );
        }
    }

    public function down(Schema $schema) : void
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
