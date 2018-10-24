<?php

namespace Surfnet\StepupMiddleware\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181023151546 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ra_listing DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE ra_listing ADD id INT AUTO_INCREMENT PRIMARY KEY NOT NULL, ADD ra_institution VARCHAR(255) NOT NULL COMMENT \'(DC2Type:institution)\'');
        $this->addSql('CREATE UNIQUE INDEX idx_ra_listing_unique_identity_institution ON ra_listing (identity_id, ra_institution)');
        $this->addSql('ALTER TABLE ra_candidate DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE ra_candidate ADD ra_institution VARCHAR(255) NOT NULL COMMENT \'(DC2Type:institution)\'');
        $this->addSql('CREATE UNIQUE INDEX idx_ra_candidate_unique_identity_institution ON ra_candidate (identity_id, ra_institution)');
        $this->addSql('ALTER TABLE ra_candidate ADD id INT');
        $this->addSql('ALTER TABLE ra_candidate ADD INDEX(`id`)');
        $this->addSql('ALTER TABLE ra_candidate CHANGE id id INT NOT NULL AUTO_INCREMENT');
        $this->addSql('ALTER TABLE ra_candidate ADD PRIMARY KEY (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ra_candidate MODIFY id INT NOT NULL');
        $this->addSql('DROP INDEX idx_ra_candidate_unique_identity_institution ON ra_candidate');
        $this->addSql('ALTER TABLE ra_candidate DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE ra_candidate DROP id, DROP ra_institution');
        $this->addSql('ALTER TABLE ra_candidate ADD PRIMARY KEY (identity_id)');
        $this->addSql('ALTER TABLE ra_listing MODIFY id INT NOT NULL');
        $this->addSql('DROP INDEX idx_ra_listing_unique_identity_institution ON ra_listing');
        $this->addSql('ALTER TABLE ra_listing DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE ra_listing DROP id, DROP ra_institution');
        $this->addSql('ALTER TABLE ra_listing ADD PRIMARY KEY (identity_id)');
    }

    public function postUp(Schema $schema)
    {
        $this->connection->executeQuery('UPDATE ra_listing SET ra_institution=institution');
        $this->connection->executeQuery('UPDATE ra_candidate SET ra_institution=institution');
    }
}
