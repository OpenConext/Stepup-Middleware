<?php

namespace Surfnet\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20160719090052 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // This migration used to create InstitutionConfiguration for institutions. We no longer support this construction
        // as the code required to achieve this is no longer supported in our current Symfony version.
        //
        // Users migrating from a pre InstitutionConfiguration era might run into problems here. I suggest they watch
        // git history of this file and work from there to create a custom migration.
        $this->write('No up migration executed: this was a data migration that we no longer support.');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // No down-migration needed as the structure has not changed.
        $this->write('No down migration executed: this was a data only migration.');
    }
}
