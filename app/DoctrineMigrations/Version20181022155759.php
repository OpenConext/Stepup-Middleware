<?php

namespace Surfnet\StepupMiddleware\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181022155759 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ra_listing DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE ra_listing ADD ra_institution VARCHAR(255) NOT NULL COMMENT \'(DC2Type:institution)\', CHANGE institution institution VARCHAR(255) NOT NULL COMMENT \'(DC2Type:institution)\', CHANGE common_name common_name VARCHAR(255) NOT NULL COMMENT \'(DC2Type:stepup_common_name)\', CHANGE email email VARCHAR(255) NOT NULL COMMENT \'(DC2Type:stepup_email)\', CHANGE role role VARCHAR(20) NOT NULL COMMENT \'(DC2Type:authority_role)\', CHANGE location location LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:stepup_location)\', CHANGE contact_information contact_information LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:stepup_contact_information)\'');
        $this->addSql('ALTER TABLE ra_listing ADD PRIMARY KEY (identity_id, ra_institution)');
        $this->addSql('ALTER TABLE ra_candidate DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE ra_candidate ADD ra_institution VARCHAR(255) NOT NULL COMMENT \'(DC2Type:institution)\', CHANGE institution institution VARCHAR(255) NOT NULL COMMENT \'(DC2Type:institution)\', CHANGE common_name common_name VARCHAR(255) NOT NULL COMMENT \'(DC2Type:stepup_common_name)\', CHANGE email email VARCHAR(255) NOT NULL COMMENT \'(DC2Type:stepup_email)\', CHANGE name_id name_id VARCHAR(255) NOT NULL COMMENT \'(DC2Type:stepup_name_id)\'');
        $this->addSql('ALTER TABLE ra_candidate ADD PRIMARY KEY (identity_id, ra_institution)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ra_candidate DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE ra_candidate DROP ra_institution, CHANGE institution institution VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE name_id name_id VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE common_name common_name VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE email email VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE ra_candidate ADD PRIMARY KEY (identity_id)');
        $this->addSql('ALTER TABLE ra_listing DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE ra_listing DROP ra_institution, CHANGE institution institution VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE common_name common_name VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE email email VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE role role VARCHAR(20) NOT NULL COLLATE utf8_unicode_ci, CHANGE location location LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci, CHANGE contact_information contact_information LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE ra_listing ADD PRIMARY KEY (identity_id)');
    }

    public function postUp(Schema $schema)
    {
        $this->addSql('UPDATE ra_listing SET ra_institution=institution');
        $this->addSql('UPDATE ra_candidate SET ra_institution=institution');
    }


}
