<?php

namespace Surfnet\StepupMiddleware\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141031133057 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $sql = <<<SQL
            CREATE TABLE event_stream (
                uuid varchar(36) NOT NULL,
                playhead int(11) NOT NULL,
                metadata text NOT NULL,
                payload text NOT NULL,
                recorded_on varchar(32) NOT NULL,
                type varchar(150) NOT NULL,
                PRIMARY KEY (uuid),
                UNIQUE KEY unique_playhead (playhead)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;

        $this->addSql($sql);
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE event_stream');
    }
}
