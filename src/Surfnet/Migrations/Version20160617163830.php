<?php

namespace Surfnet\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160617163830 extends AbstractMigration
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
            'CREATE TABLE ra_location (ra_location_id VARCHAR(36) NOT NULL, institution VARCHAR(255) NOT NULL, ra_location_name VARCHAR(255) NOT NULL, location LONGTEXT NOT NULL, contact_information LONGTEXT NOT NULL, INDEX idx_ra_location_institution (institution), PRIMARY KEY(ra_location_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB',
        );
        $this->addSql(
            'ALTER TABLE audit_log CHANGE actor_institution actor_institution VARCHAR(255) DEFAULT NULL, CHANGE identity_institution identity_institution VARCHAR(255) NOT NULL, CHANGE recorded_on recorded_on DATETIME NOT NULL, CHANGE actor_common_name actor_common_name VARCHAR(255) DEFAULT NULL',
        );
        $this->addSql(
            'ALTER TABLE identity CHANGE name_id name_id VARCHAR(255) NOT NULL, CHANGE institution institution VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE common_name common_name VARCHAR(255) NOT NULL, CHANGE preferred_locale preferred_locale VARCHAR(255) NOT NULL',
        );
        $this->addSql('ALTER TABLE institution_listing CHANGE institution institution VARCHAR(255) NOT NULL');
        $this->addSql(
            'ALTER TABLE ra_candidate CHANGE institution institution VARCHAR(255) NOT NULL, CHANGE common_name common_name VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE name_id name_id VARCHAR(255) NOT NULL',
        );
        $this->addSql(
            'ALTER TABLE ra_listing CHANGE institution institution VARCHAR(255) NOT NULL, CHANGE common_name common_name VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE role role VARCHAR(20) NOT NULL, CHANGE location location LONGTEXT DEFAULT NULL, CHANGE contact_information contact_information LONGTEXT DEFAULT NULL',
        );
        $this->addSql(
            'ALTER TABLE ra_second_factor CHANGE institution institution VARCHAR(255) NOT NULL, CHANGE name name VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE status status INT NOT NULL, CHANGE document_number document_number VARCHAR(255) NOT NULL',
        );
        $this->addSql(
            'ALTER TABLE second_factor_revocation CHANGE institution institution VARCHAR(255) NOT NULL, CHANGE recorded_on recorded_on DATETIME NOT NULL',
        );
        $this->addSql('ALTER TABLE sraa CHANGE name_id name_id VARCHAR(200) NOT NULL');
        $this->addSql(
            'ALTER TABLE unverified_second_factor CHANGE identity_id identity_id VARCHAR(36) NOT NULL, CHANGE verification_nonce_valid_until verification_nonce_valid_until DATETIME NOT NULL',
        );
        $this->addSql('ALTER TABLE vetted_second_factor CHANGE identity_id identity_id VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE whitelist_entry CHANGE institution institution VARCHAR(255) NOT NULL');
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

        $this->addSql('DROP TABLE ra_location');
        $this->addSql(
            'ALTER TABLE audit_log CHANGE actor_common_name actor_common_name VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, CHANGE actor_institution actor_institution VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, CHANGE identity_institution identity_institution VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE recorded_on recorded_on DATETIME NOT NULL',
        );
        $this->addSql(
            'ALTER TABLE identity CHANGE name_id name_id VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE common_name common_name VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE institution institution VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE email email VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE preferred_locale preferred_locale VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci',
        );
        $this->addSql(
            'ALTER TABLE institution_listing CHANGE institution institution VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci',
        );
        $this->addSql(
            'ALTER TABLE ra_candidate CHANGE institution institution VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE name_id name_id VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE common_name common_name VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE email email VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci',
        );
        $this->addSql(
            'ALTER TABLE ra_listing CHANGE institution institution VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE common_name common_name VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE email email VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE role role VARCHAR(20) NOT NULL COLLATE utf8_unicode_ci, CHANGE location location LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci, CHANGE contact_information contact_information LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci',
        );
        $this->addSql(
            'ALTER TABLE ra_second_factor CHANGE institution institution VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE name name VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE email email VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE document_number document_number VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, CHANGE status status INT NOT NULL',
        );
        $this->addSql(
            'ALTER TABLE second_factor_revocation CHANGE institution institution VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE recorded_on recorded_on DATETIME NOT NULL',
        );
        $this->addSql('ALTER TABLE sraa CHANGE name_id name_id VARCHAR(200) NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql(
            'ALTER TABLE unverified_second_factor CHANGE identity_id identity_id VARCHAR(36) DEFAULT NULL COLLATE utf8_unicode_ci, CHANGE verification_nonce_valid_until verification_nonce_valid_until DATETIME NOT NULL',
        );
        $this->addSql(
            'ALTER TABLE vetted_second_factor CHANGE identity_id identity_id VARCHAR(36) DEFAULT NULL COLLATE utf8_unicode_ci',
        );
        $this->addSql(
            'ALTER TABLE whitelist_entry CHANGE institution institution VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci',
        );
    }
}
