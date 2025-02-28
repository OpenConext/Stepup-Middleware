<?php

/**
 * Copyright 2020 SURFnet bv
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Surfnet\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Surfnet\Stepup\MigrationsFactory\ConfigurationAwareMigrationInterface;
use Surfnet\Stepup\MigrationsFactory\ConfigurationAwareMigrationTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200114161618 extends AbstractMigration implements ConfigurationAwareMigrationInterface
{
    use ConfigurationAwareMigrationTrait;

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // Convert all available institutions occurrences to lower case (type="institution")
        $this->addSql('UPDATE audit_log SET actor_institution=LOWER(actor_institution)');
        $this->addSql('UPDATE audit_log SET identity_institution=LOWER(identity_institution)');
        $this->addSql('UPDATE audit_log SET ra_institution=LOWER(ra_institution)');
        $this->addSql('UPDATE identity SET institution=LOWER(institution)');
        $this->addSql('UPDATE institution_listing SET institution=LOWER(institution)');
        $this->addSql('UPDATE ra_candidate SET institution=LOWER(institution)');
        $this->addSql('UPDATE ra_candidate SET ra_institution=LOWER(ra_institution)');
        $this->addSql('UPDATE ra_listing SET institution=LOWER(institution)');
        $this->addSql('UPDATE ra_listing SET ra_institution=LOWER(ra_institution)');
        $this->addSql('UPDATE ra_second_factor SET institution=LOWER(institution)');
        $this->addSql('UPDATE second_factor_revocation SET institution=LOWER(institution)');
        $this->addSql('UPDATE whitelist_entry SET institution=LOWER(institution)');
        $this->addSql('UPDATE verified_second_factor SET institution=LOWER(institution)');

        // Convert all available configuration institutions occurrences to lower case (type="stepup_configuration_institution")
        $this->addSql('UPDATE allowed_second_factor SET institution=LOWER(institution)');
        $this->addSql('UPDATE configured_institution SET institution=LOWER(institution)');
        $this->addSql('UPDATE institution_authorization SET institution=LOWER(institution)');
        $this->addSql('UPDATE institution_authorization SET institution_relation=LOWER(institution_relation)');
        $this->addSql('UPDATE institution_configuration_options SET institution=LOWER(institution)');
        $this->addSql('UPDATE ra_location SET institution=LOWER(institution)');

        // Convert all GW institutions to lowercase
        $gatewaySchema = $this->getGatewaySchema();
        $this->addSql(sprintf('UPDATE %s.whitelist_entry SET institution=LOWER(institution)', $gatewaySchema));
        $this->addSql(sprintf('UPDATE %s.second_factor SET institution=LOWER(institution)', $gatewaySchema));
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->throwIrreversibleMigrationException('This migration is irreversible');
    }
}
