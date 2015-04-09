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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Projector;

use Broadway\Domain\DomainMessage;
use Broadway\ReadModel\Projector;
use Surfnet\Stepup\Configuration\Event\ConfigurationUpdatedEvent;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Entity\EmailTemplate;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Repository\EmailTemplateRepository;

final class EmailTemplatesProjector extends Projector
{
    /**
     * @var \Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Repository\EmailTemplateRepository
     */
    private $repository;

    public function __construct(EmailTemplateRepository $repository)
    {
        $this->repository = $repository;
    }

    public function applyConfigurationUpdatedEvent(ConfigurationUpdatedEvent $event)
    {
        $this->repository->removeAll();

        foreach ($event->newConfiguration['email_templates'] as $name => $templates) {
            foreach ($templates as $locale => $htmlContent) {
                $this->repository->save(new EmailTemplate($name, $locale, $htmlContent));
            }
        }
    }
}
