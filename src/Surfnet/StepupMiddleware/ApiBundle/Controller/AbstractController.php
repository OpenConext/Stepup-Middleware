<?php

/**
 * Copyright 2024 SURFnet bv
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

namespace Surfnet\StepupMiddleware\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;

/**
 * @SuppressWarnings("PHPMD.NumberOfChildren") we simply have a lot of commands
 */
class AbstractController extends SymfonyAbstractController
{
    protected function denyAccessUnlessGrantedOneOff(mixed $attribute, mixed $subject = null, string $message = 'Access Denied.'): void
    {
        if (is_array($attribute)) {
            foreach ($attribute as $role) {
                if ($this->isGranted($role, $subject)) {
                    return;
                }
            }

            throw $this->createAccessDeniedException($message);
        }
        parent::denyAccessUnlessGranted($attribute, $subject, $message);
    }
}
