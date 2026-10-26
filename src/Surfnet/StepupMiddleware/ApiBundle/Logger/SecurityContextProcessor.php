<?php

declare(strict_types=1);

/**
 * Copyright 2026 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\ApiBundle\Logger;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\RequestStack;

#[AutoconfigureTag('monolog.processor', ['channel' => 'request'])]
class SecurityContextProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Security $security,
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $extra = $record->extra;

        $request = $this->requestStack->getCurrentRequest();
        if ($request !== null) {
            $extra['http_method'] = $request->getMethod();
            $extra['path'] = $request->getPathInfo();

            $route = $request->attributes->get('_route');
            if (is_string($route) && $route !== '') {
                $extra['route'] = $route;
            }

            $clientIp = $request->getClientIp();
            if ($clientIp !== null && $clientIp !== '') {
                $extra['client_ip'] = $clientIp;
            }
        }

        $user = $this->security->getUser();
        if ($user !== null) {
            $extra['user'] = $user->getUserIdentifier();
        }

        return $record->with(extra: $extra);
    }
}
