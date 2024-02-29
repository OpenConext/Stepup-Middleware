<?php

namespace Surfnet\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141128143908 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql(
            'CREATE TABLE verified_second_factor (id VARCHAR(36) NOT NULL, identity_id VARCHAR(36) DEFAULT NULL, type VARCHAR(16) NOT NULL, second_factor_identifier VARCHAR(32) NOT NULL, INDEX IDX_7964F91CFF3ED4A8 (identity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB',
        );
        $this->addSql(
            'ALTER TABLE verified_second_factor ADD CONSTRAINT FK_7964F91CFF3ED4A8 FOREIGN KEY (identity_id) REFERENCES identity (id)',
        );
        $this->addSql('ALTER TABLE unverified_second_factor DROP email_verification_nonce');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql('DROP TABLE verified_second_factor');
        $this->addSql(
            'ALTER TABLE unverified_second_factor ADD email_verification_nonce VARCHAR(32) NOT NULL COLLATE utf8_unicode_ci',
        );
    }
}
