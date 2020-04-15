<?php

namespace Surfnet\StepupMiddleware\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20200416135127 extends AbstractMigration
{
    private $view = <<<SQL
CREATE VIEW `view_ra_candidate` AS
(
    SELECT DISTINCT i.id AS identity_id,
        i.institution,
        i.common_name,
        i.email,
        i.name_id,
        a.institution AS ra_institution
    FROM identity i
         INNER JOIN vetted_second_factor vsf ON vsf.identity_id = i.id
         INNER JOIN institution_authorization a ON (
        a.institution_role = 'select_raa' AND a.institution_relation = i.institution
    )
    WHERE NOT EXISTS(SELECT 1
        FROM ra_listing AS l
        WHERE l.identity_id = i.id AND l.ra_institution = a.institution
        )
    );
SQL;

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql($this->view);

        $this->addSql('DROP TABLE ra_candidate');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->throwIrreversibleMigrationException('This migration is irreversible and cannot be reverted because it will need a replay on the RACandidateProjector.');
    }
}
