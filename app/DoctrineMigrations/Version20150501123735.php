<?php

namespace Surfnet\StepupMiddleware\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150501123735 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE audit_log ADD COLUMN uuid VARCHAR(36) DEFAULT NULL');
        $this->addSql('UPDATE audit_log set uuid = UUID() WHERE 1 = 1');
        $this->addSql('ALTER TABLE audit_log CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE audit_log DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE audit_log DROP COLUMN id');
        $this->addSql('ALTER TABLE audit_log CHANGE uuid id VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE audit_log ADD PRIMARY KEY (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE audit_log DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE audit_log CHANGE id uuid VARCHAR(36)');
        $this->addSql('ALTER TABLE audit_log ADD id INT PRIMARY KEY AUTO_INCREMENT');
        $this->addSql('ALTER TABLE audit_log DROP COLUMN uuid');
    }
}
