<?php

namespace Surfnet\StepupMiddleware\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150330160809 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('CREATE TABLE audit_log (id INT AUTO_INCREMENT NOT NULL, actor_id VARCHAR(36) DEFAULT NULL, actor_institution VARCHAR(255) DEFAULT NULL, identity_id VARCHAR(36) NOT NULL, identity_institution VARCHAR(255) NOT NULL, second_factor_id VARCHAR(36) DEFAULT NULL, second_factor_type VARCHAR(36) DEFAULT NULL, action VARCHAR(255) NOT NULL, recorded_on DATETIME NOT NULL, INDEX idx_auditlog_actorid (actor_id), INDEX idx_auditlog_identityid (identity_id), INDEX idx_auditlog_identityinstitution (identity_institution), INDEX idx_auditlog_secondfactorid (second_factor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('DROP TABLE audit_log');
    }
}
