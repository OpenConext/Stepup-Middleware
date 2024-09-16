<?php

namespace Surfnet\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150507124421 extends AbstractMigration
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

        $this->addSql('ALTER TABLE raa ADD COLUMN uuid VARCHAR(36) DEFAULT NULL');
        $this->addSql('UPDATE raa set uuid = UUID() WHERE 1 = 1');
        $this->addSql('ALTER TABLE raa CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE raa DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE raa DROP COLUMN id');
        $this->addSql('ALTER TABLE raa CHANGE uuid id VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE raa ADD PRIMARY KEY (id)');

        $this->addSql('ALTER TABLE ra ADD COLUMN uuid VARCHAR(36) DEFAULT NULL');
        $this->addSql('UPDATE ra set uuid = UUID() WHERE 1 = 1');
        $this->addSql('ALTER TABLE ra CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE ra DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE ra DROP COLUMN id');
        $this->addSql('ALTER TABLE ra CHANGE uuid id VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE ra ADD PRIMARY KEY (id)');

        $this->addSql(
            'ALTER TABLE raa CHANGE institution institution VARCHAR(255) NOT NULL, CHANGE name_id name_id VARCHAR(255) NOT NULL',
        );
        $this->addSql(
            'ALTER TABLE ra CHANGE institution institution VARCHAR(255) NOT NULL, CHANGE name_id name_id VARCHAR(255) NOT NULL',
        );
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

        $this->addSql('ALTER TABLE ra DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE ra CHANGE id uuid VARCHAR(36)');
        $this->addSql('ALTER TABLE ra ADD id INT PRIMARY KEY AUTO_INCREMENT');
        $this->addSql('ALTER TABLE ra DROP COLUMN uuid');

        $this->addSql('ALTER TABLE raa DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE raa CHANGE id uuid VARCHAR(36)');
        $this->addSql('ALTER TABLE raa ADD id INT PRIMARY KEY AUTO_INCREMENT');
        $this->addSql('ALTER TABLE raa DROP COLUMN uuid');

        $this->addSql(
            'ALTER TABLE ra CHANGE institution institution VARCHAR(150) NOT NULL COLLATE utf8_unicode_ci, CHANGE name_id name_id VARCHAR(150) NOT NULL COLLATE utf8_unicode_ci',
        );
        $this->addSql(
            'ALTER TABLE raa CHANGE institution institution VARCHAR(150) NOT NULL COLLATE utf8_unicode_ci, CHANGE name_id name_id VARCHAR(150) NOT NULL COLLATE utf8_unicode_ci',
        );
    }
}
