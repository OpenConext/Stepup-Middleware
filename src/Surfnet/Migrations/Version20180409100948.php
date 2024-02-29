<?php

namespace Surfnet\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Surfnet\Stepup\Configuration\Value\NumberOfTokensPerIdentityOption;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180409100948 extends AbstractMigration
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

        $this->addSql(
            sprintf(
                "ALTER TABLE institution_configuration_options ADD number_of_tokens_per_identity_option TINYINT(1) DEFAULT '%d' NOT NULL",
                NumberOfTokensPerIdentityOption::DISABLED,
            ),
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
        $this->addSql('ALTER TABLE institution_configuration_options DROP number_of_tokens_per_identity_option');
    }
}
