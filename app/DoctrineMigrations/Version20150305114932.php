<?php

namespace Surfnet\StepupMiddleware\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150305114932 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('DROP INDEX idx_ra_second_factor_type ON ra_second_factor');
        $this->addSql('DROP INDEX idx_ra_second_factor_status ON ra_second_factor');
        $this->addSql('DROP INDEX idx_ra_second_factor_name ON ra_second_factor');
        $this->addSql('DROP INDEX idx_ra_second_factor_email ON ra_second_factor');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('CREATE INDEX idx_ra_second_factor_type ON ra_second_factor (type)');
        $this->addSql('CREATE INDEX idx_ra_second_factor_status ON ra_second_factor (status)');
        $this->addSql('CREATE INDEX idx_ra_second_factor_name ON ra_second_factor (name)');
        $this->addSql('CREATE INDEX idx_ra_second_factor_email ON ra_second_factor (email)');
    }
}
