<?php

namespace Surfnet\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drops the UUID primary key, removes the UNIQUE constraint on (uuid, playhead) and make (uuid, playhead) the primary
 * key.
 */
class Version20141112094457 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('ALTER TABLE event_stream DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE event_stream DROP INDEX unique_uuid_playhead');
        $this->addSql(
            'ALTER TABLE event_stream ADD CONSTRAINT pk_event_stream_uuid_playhead PRIMARY KEY (uuid, playhead)'
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('ALTER TABLE event_stream DROP PRIMARY KEY');
        $this->addSql("ALTER TABLE event_stream ADD UNIQUE INDEX unique_uuid_playhead (uuid, playhead)");
        $this->addSql('ALTER TABLE event_stream ADD CONSTRAINT pk_event_stream_uuid PRIMARY KEY (uuid)');
    }
}
