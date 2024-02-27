<?php

/**
 * Copyright 2014 SURFnet bv
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

namespace Surfnet\StepupMiddleware\ManagementBundle\Configuration\Projector;

use Broadway\ReadModel\Projector;
use Surfnet\Stepup\Configuration\Event\EmailTemplatesUpdatedEvent;
use Surfnet\StepupMiddleware\ManagementBundle\Configuration\Entity\EmailTemplate;
use Surfnet\StepupMiddleware\ManagementBundle\Configuration\Repository\EmailTemplateRepository;

final class EmailTemplatesProjector extends Projector
{
    public function __construct(
        private readonly EmailTemplateRepository $repository,
    ) {
    }

    public function applyEmailTemplatesUpdatedEvent(EmailTemplatesUpdatedEvent $event): void
    {
        $this->repository->removeAll();

        foreach ($event->emailTemplates as $name => $templates) {
            foreach ($templates as $locale => $htmlContent) {
                $this->repository->save(EmailTemplate::create($name, $locale, $htmlContent));
            }
        }
    }
}
