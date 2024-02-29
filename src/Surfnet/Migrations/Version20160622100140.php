<?php

namespace Surfnet\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160622100140 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql(
            'ALTER TABLE ra_second_factor CHANGE institution institution VARCHAR(255) NOT NULL, CHANGE name name VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE status status INT NOT NULL, CHANGE document_number document_number VARCHAR(255) DEFAULT NULL',
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql(
            'ALTER TABLE ra_second_factor CHANGE institution institution VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE name name VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE email email VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE document_number document_number VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE status status INT NOT NULL',
        );
    }
}
