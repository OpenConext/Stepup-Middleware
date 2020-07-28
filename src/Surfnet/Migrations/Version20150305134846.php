<?php

namespace Surfnet\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150305134846 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE ra_second_factor ADD status_int INT NOT NULL');
        $this->addSql('UPDATE ra_second_factor SET status_int=0 WHERE `status`="unverified"');
        $this->addSql('UPDATE ra_second_factor SET status_int=10 WHERE `status`="verified"');
        $this->addSql('UPDATE ra_second_factor SET status_int=20 WHERE `status`="vetted"');
        $this->addSql('UPDATE ra_second_factor SET status_int=30 WHERE `status`="revoked"');
        $this->addSql('ALTER TABLE ra_second_factor DROP `status`');
        $this->addSql('ALTER TABLE ra_second_factor CHANGE status_int `status` INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE ra_second_factor CHANGE `status` `status` VARCHAR(10) NOT NULL COLLATE utf8_unicode_ci');

        $this->addSql('ALTER TABLE ra_second_factor ADD status_string VARCHAR(10) NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('UPDATE ra_second_factor SET status_string="unverified" WHERE `status`=0');
        $this->addSql('UPDATE ra_second_factor SET status_string="verified" WHERE `status`=10');
        $this->addSql('UPDATE ra_second_factor SET status_string="vetted" WHERE `status`=20');
        $this->addSql('UPDATE ra_second_factor SET status_string="revoked" WHERE `status`=30');
        $this->addSql('ALTER TABLE ra_second_factor DROP `status`');
        $this->addSql('ALTER TABLE ra_second_factor CHANGE status_string `status` VARCHAR(10) NOT NULL COLLATE utf8_unicode_ci');
    }
}
