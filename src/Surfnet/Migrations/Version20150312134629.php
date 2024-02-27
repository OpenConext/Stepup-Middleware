<?php

namespace Surfnet\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150312134629 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql('ALTER TABLE ra_second_factor CHANGE second_factor_id second_factor_id VARCHAR(255) NOT NULL');
        $this->addSql(
            'ALTER TABLE verified_second_factor CHANGE second_factor_identifier second_factor_identifier VARCHAR(255) NOT NULL',
        );
        $this->addSql(
            'ALTER TABLE unverified_second_factor CHANGE second_factor_identifier second_factor_identifier VARCHAR(255) NOT NULL',
        );
        $this->addSql(
            'ALTER TABLE vetted_second_factor CHANGE second_factor_identifier second_factor_identifier VARCHAR(255) NOT NULL',
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql(
            'ALTER TABLE ra_second_factor CHANGE second_factor_id second_factor_id VARCHAR(36) NOT NULL COLLATE utf8_unicode_ci',
        );
        $this->addSql(
            'ALTER TABLE unverified_second_factor CHANGE second_factor_identifier second_factor_identifier VARCHAR(32) NOT NULL COLLATE utf8_unicode_ci',
        );
        $this->addSql(
            'ALTER TABLE verified_second_factor CHANGE second_factor_identifier second_factor_identifier VARCHAR(32) NOT NULL COLLATE utf8_unicode_ci',
        );
        $this->addSql(
            'ALTER TABLE vetted_second_factor CHANGE second_factor_identifier second_factor_identifier VARCHAR(32) NOT NULL COLLATE utf8_unicode_ci',
        );
    }
}
