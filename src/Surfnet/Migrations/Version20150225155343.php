<?php

namespace Surfnet\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150225155343 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql(
            'CREATE TABLE ra_second_factor (id VARCHAR(36) NOT NULL, type VARCHAR(16) NOT NULL, second_factor_id VARCHAR(36) NOT NULL, status VARCHAR(10) NOT NULL, identity_id VARCHAR(36) NOT NULL, institution VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, INDEX idx_ra_second_factor_type (type), INDEX idx_ra_second_factor_second_factor_id (second_factor_id), INDEX idx_ra_second_factor_status (status), INDEX idx_ra_second_factor_identity_id (identity_id), INDEX idx_ra_second_factor_institution (institution), INDEX idx_ra_second_factor_name (name), INDEX idx_ra_second_factor_email (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB',
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql('DROP TABLE ra_second_factor');
    }
}
