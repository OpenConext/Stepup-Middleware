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

namespace Surfnet\StepupMiddleware\ApiBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\ToolEvents;

/**
 * Remove views from schematool to prevent unwanted changes
 * This is used to prevent schema changes when using the RaCandidate view.
 */
class IgnoreTablesListener implements EventSubscriber
{
    private $ignoredTables = [
        'middleware_test.view_ra_candidate',
        'middleware.view_ra_candidate',
    ];

    public function postGenerateSchema(GenerateSchemaEventArgs $args)
    {
        $schema = $args->getSchema();

        $tableNames = $schema->getTableNames();
        foreach ($tableNames as $tableName) {
            if (in_array($tableName, $this->ignoredTables)) {
                // remove table from schema
                $schema->dropTable($tableName);
            }
        }
    }

    public function postGenerateSchemaTable(GenerateSchemaTableEventArgs $args)
    {
        $schema = $args->getSchema();

        $tableNames = $schema->getTableNames();
        foreach ($tableNames as $tableName) {
            if (in_array($tableName, $this->ignoredTables)) {
                // remove table from schema
                $schema->dropTable($tableName);
            }
        }
    }

    public function getSubscribedEvents()
    {
        return array(
            ToolEvents::postGenerateSchema,
            ToolEvents::postGenerateSchemaTable,
        );
    }
}
