<?php

namespace Surfnet\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150604143756 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE sensitive_data_stream');
        $this->addSql('
            CREATE TABLE event_stream_sensitive_data (
              identity_id VARCHAR(36) NOT NULL,
              playhead INT NOT NULL,
              sensitive_data LONGTEXT NOT NULL,
              PRIMARY KEY (identity_id, playhead)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE event_stream_sensitive_data');
        $this->addSql('
            CREATE TABLE sensitive_data_stream (
              id VARCHAR(36) NOT NULL,
              identity_id VARCHAR(36) NOT NULL,
              playhead INT NOT NULL,
              sensitive_data LONGTEXT DEFAULT NULL,
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ');
    }
}
