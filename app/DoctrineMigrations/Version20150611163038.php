<?php

namespace Surfnet\StepupMiddleware\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150611163038 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE verified_second_factor DROP FOREIGN KEY fk_verified_second_factor_identity');
        $this->addSql('DROP INDEX IDX_7964F91CFF3ED4A8 ON verified_second_factor');
        $this->addSql('ALTER TABLE verified_second_factor ADD institution VARCHAR(255) NOT NULL, ADD common_name VARCHAR(255) NOT NULL, CHANGE identity_id identity_id VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE vetted_second_factor DROP FOREIGN KEY fk_vetted_second_factor_identity');
        $this->addSql('DROP INDEX IDX_29F96B72FF3ED4A8 ON vetted_second_factor');
        $this->addSql('ALTER TABLE unverified_second_factor DROP FOREIGN KEY fk_unverified_second_factor_identity');
        $this->addSql('DROP INDEX IDX_D79226A2FF3ED4A8 ON unverified_second_factor');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE unverified_second_factor ADD CONSTRAINT fk_unverified_second_factor_identity FOREIGN KEY (identity_id) REFERENCES identity (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_D79226A2FF3ED4A8 ON unverified_second_factor (identity_id)');
        $this->addSql('ALTER TABLE verified_second_factor DROP institution, DROP common_name, CHANGE identity_id identity_id VARCHAR(36) DEFAULT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE verified_second_factor ADD CONSTRAINT fk_verified_second_factor_identity FOREIGN KEY (identity_id) REFERENCES identity (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_7964F91CFF3ED4A8 ON verified_second_factor (identity_id)');
        $this->addSql('ALTER TABLE vetted_second_factor ADD CONSTRAINT fk_vetted_second_factor_identity FOREIGN KEY (identity_id) REFERENCES identity (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_29F96B72FF3ED4A8 ON vetted_second_factor (identity_id)');
    }
}
