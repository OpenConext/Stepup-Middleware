<?php

/**
 * Copyright 2021 SURFnet bv
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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Identity\Projector\Event;

use Surfnet\Stepup\Identity\AuditLog\Metadata;
use Surfnet\Stepup\Identity\Event\AuditableSourceAndTargetEvent;

final class SourceAndTargetEventStub implements AuditableSourceAndTargetEvent
{

    /**
     * @var Metadata
     */
    private $sourceMetadata;
    /**
     * @var Metadata
     */
    private $destinationMetadata;

    public function __construct(Metadata $sourceMetadata, Metadata $destinationMetadata)
    {

        $this->sourceMetadata = $sourceMetadata;
        $this->destinationMetadata = $destinationMetadata;
    }

    public function getAuditLogMetadata()
    {
        return $this->destinationMetadata;
    }

    public function getAuditLogMetadataSource()
    {
        return $this->sourceMetadata;
    }
}
