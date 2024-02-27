<?php

namespace Surfnet\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150611105956 extends AbstractMigration
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

        $this->addSql('ALTER TABLE unverified_second_factor DROP FOREIGN KEY FK_D79226A2FF3ED4A8');
        $this->addSql('ALTER TABLE verified_second_factor DROP FOREIGN KEY FK_7964F91CFF3ED4A8');
        $this->addSql('ALTER TABLE vetted_second_factor DROP FOREIGN KEY FK_29F96B72FF3ED4A8');
        $this->addSql(
            'ALTER TABLE unverified_second_factor ADD CONSTRAINT fk_unverified_second_factor_identity FOREIGN KEY (identity_id) REFERENCES identity (id) ON DELETE CASCADE',
        );
        $this->addSql(
            'ALTER TABLE verified_second_factor ADD CONSTRAINT fk_verified_second_factor_identity FOREIGN KEY (identity_id) REFERENCES identity (id) ON DELETE CASCADE',
        );
        $this->addSql(
            'ALTER TABLE vetted_second_factor ADD CONSTRAINT fk_vetted_second_factor_identity FOREIGN KEY (identity_id) REFERENCES identity (id) ON DELETE CASCADE',
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

        $this->addSql('ALTER TABLE unverified_second_factor DROP FOREIGN KEY fk_unverified_second_factor_identity');
        $this->addSql('ALTER TABLE verified_second_factor DROP FOREIGN KEY fk_verified_second_factor_identity');
        $this->addSql('ALTER TABLE vetted_second_factor DROP FOREIGN KEY fk_vetted_second_factor_identity');
        $this->addSql(
            'ALTER TABLE unverified_second_factor ADD CONSTRAINT FK_D79226A2FF3ED4A8 FOREIGN KEY (identity_id) REFERENCES identity (id)',
        );
        $this->addSql(
            'ALTER TABLE verified_second_factor ADD CONSTRAINT FK_7964F91CFF3ED4A8 FOREIGN KEY (identity_id) REFERENCES identity (id)',
        );
        $this->addSql(
            'ALTER TABLE vetted_second_factor ADD CONSTRAINT FK_29F96B72FF3ED4A8 FOREIGN KEY (identity_id) REFERENCES identity (id)',
        );
    }
}
