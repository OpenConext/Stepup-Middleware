<?php

/**
 * Copyright 2018 SURFnet bv
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
