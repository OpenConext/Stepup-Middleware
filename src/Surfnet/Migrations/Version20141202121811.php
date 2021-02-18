<?php

namespace Surfnet\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141202121811 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('DROP TABLE second_factor');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('CREATE TABLE second_factor (id VARCHAR(36) NOT NULL COLLATE utf8_unicode_ci, identity_id VARCHAR(36) DEFAULT NULL COLLATE utf8_unicode_ci, type VARCHAR(16) NOT NULL COLLATE utf8_unicode_ci, second_factor_identifier VARCHAR(32) NOT NULL COLLATE utf8_unicode_ci, INDEX IDX_1806C29EFF3ED4A8 (identity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE second_factor ADD CONSTRAINT FK_1806C29EFF3ED4A8 FOREIGN KEY (identity_id) REFERENCES identity (id)');
    }
}
