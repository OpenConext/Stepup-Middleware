<?php

namespace Surfnet\StepupMiddleware\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180918120046 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            ALTER TABLE institution_configuration_options 
              ADD use_ra_option LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:stepup_select_ra_option)\', 
              ADD use_raa_option LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:stepup_select_raa_option)\', 
              ADD select_raa_option LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:stepup_select_raa_option)\'
        ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            ALTER TABLE institution_configuration_options 
              DROP use_ra_option, 
              DROP use_raa_option, 
              DROP select_raa_option
        ');
    }
}
