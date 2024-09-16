<?php

namespace Surfnet\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150305115030 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql('CREATE FULLTEXT INDEX idx_ra_second_factor_name ON ra_second_factor (name)');
        $this->addSql('CREATE FULLTEXT INDEX idx_ra_second_factor_email ON ra_second_factor (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql('DROP INDEX idx_ra_second_factor_name ON ra_second_factor');
        $this->addSql('DROP INDEX idx_ra_second_factor_email ON ra_second_factor');
    }
}
