<?php

namespace Surfnet\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190211163604 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE INDEX idx_ra_listing_ra_institution ON ra_listing (ra_institution)');
        $this->addSql('CREATE INDEX idx_auditlog_ra_institution ON audit_log (ra_institution)');
        $this->addSql('CREATE INDEX idx_institution ON verified_second_factor (institution)');
        $this->addSql('DROP INDEX id ON ra_candidate');
        $this->addSql('CREATE INDEX idx_ra_institution ON ra_candidate (ra_institution)');
        $this->addSql('CREATE INDEX idx_authorization ON institution_authorization (institution, institution_relation, institution_role)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX idx_auditlog_ra_institution ON audit_log');
        $this->addSql('DROP INDEX idx_authorization ON institution_authorization');
        $this->addSql('DROP INDEX idx_ra_institution ON ra_candidate');
        $this->addSql('CREATE INDEX id ON ra_candidate (id)');
        $this->addSql('DROP INDEX idx_ra_listing_ra_institution ON ra_listing');
        $this->addSql('DROP INDEX idx_institution ON verified_second_factor');
    }
}
