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

namespace Surfnet\StepupMiddleware\ManagementBundle\Configuration\Entity;

use Doctrine\ORM\Mapping as ORM;
use Rhumsaa\Uuid\Uuid;

/**
 * @ORM\Entity(
 *      repositoryClass="Surfnet\StepupMiddleware\ManagementBundle\Configuration\Repository\EmailTemplateRepository"
 * )
 * @ORM\Table(
 *      name="email_templates",
 *      indexes={}
 * )
 */
class EmailTemplate
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column
     *
     * @var string
     */
    private $id;

    /**
     * @ORM\Column
     *
     * @var string
     */
    private $name;

    /**
     * @ORM\Column
     *
     * @var string
     */
    private $locale;

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $htmlContent;

    public static function create($name, $locale, $htmlContent)
    {
        $self = new self();
        $self->id = (string) Uuid::uuid4();

        $self->name = $name;
        $self->locale = $locale;
        $self->htmlContent = $htmlContent;

        return $self;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return string
     */
    public function getHtmlContent()
    {
        return $this->htmlContent;
    }
}
