<?php

namespace Surfnet\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181001082254 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $authorizationRoles = [
            'use_ra',
            'use_raa',
            'select_raa',
        ];

        foreach ($authorizationRoles as $roleType) {
            $this->addSql(
                "INSERT IGNORE INTO institution_authorization(institution, institution_relation, institution_role)
                SELECT institution, institution, '{$roleType}' FROM institution_configuration_options;",
            );

            $this->addSql(
                "INSERT IGNORE INTO institution_authorization(institution, institution_relation, institution_role)
                SELECT institution, institution, '{$roleType}' FROM whitelist_entry;",
            );
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
