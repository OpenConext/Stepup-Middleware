<?php

declare(strict_types = 1);

/**
 * Copyright 2026 SURFnet bv
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

namespace Surfnet\StepupMiddleware\ApiBundle\Monolog\Processor;

use Monolog\LogRecord;
use Stringable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use function in_array;
use function is_array;
use function is_scalar;
use function is_string;
use function json_decode;
use function trim;

class RequestContextProcessor
{
    private const string CACHE_ATTRIBUTE = '_surfnet_request_context_processor_cached';

    private const array SECOND_FACTOR_ROUTES = [
        'unverified_second_factor',
        'verified_second_factor',
        'verified_second_factor_can_skip_prove_posession',
        'vetted_second_factor',
    ];

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request instanceof Request) {
            return $record;
        }

        $context = $this->findRequestContext($request);

        foreach ($context as $key => $value) {
            $record->extra[$key] = $value;
        }

        return $record;
    }

    /**
     * @return array<string, string>
     */
    private function findRequestContext(Request $request): array
    {
        if ($request->attributes->has(self::CACHE_ATTRIBUTE)) {
            /** @var array<string, string> */
            return $request->attributes->get(self::CACHE_ATTRIBUTE);
        }

        $context = $this->extractRequestContext($request);
        $request->attributes->set(self::CACHE_ATTRIBUTE, $context);

        return $context;
    }

    /**
     * @return array<string, string>
     */
    private function extractRequestContext(Request $request): array
    {
        $jsonData = null;
        $content = $request->getContent();
        if ($content !== '') {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                $jsonData = $decoded;
            }
        }

        $context = [];

        $identityId = $this->extractIdentityId($request, $jsonData);
        if ($identityId !== null) {
            $context['identity_id'] = $identityId;
        }

        $collabPersonId = $this->extractCollabPersonId($request, $jsonData);
        if ($collabPersonId !== null) {
            $context['collab_person_id'] = $collabPersonId;
        }

        $secondFactorId = $this->extractSecondFactorId($request, $jsonData);
        if ($secondFactorId !== null) {
            $context['second_factor_id'] = $secondFactorId;
        }

        $recoveryTokenId = $this->extractRecoveryTokenId($request, $jsonData);
        if ($recoveryTokenId !== null) {
            $context['recovery_token_id'] = $recoveryTokenId;
        }

        $nameId = $this->extractNameId($request, $jsonData);
        if ($nameId !== null) {
            $context['name_id'] = $nameId;
        }

        $institution = $this->extractInstitution($request, $jsonData);
        if ($institution !== null) {
            $context['institution'] = $institution;
        }

        return $context;
    }

    private function extractIdentityId(Request $request, ?array $jsonData): ?string
    {
        if ($request->attributes->get('_route') === 'identity' && $request->attributes->has('id')) {
            $id = $this->stringify($request->attributes->get('id'));
            if ($id !== null) {
                return $id;
            }
        }

        $value = $this->extractFromRequestOrJson($request, ['identityId', 'identity_id'], $jsonData);
        if ($value !== null) {
            return $value;
        }

        if ($jsonData !== null) {
            if (isset($jsonData['command']['name'])
                && is_string($jsonData['command']['name'])
                && in_array($jsonData['command']['name'], ['Identity:CreateIdentity', 'Identity:UpdateIdentity'], true)
                && isset($jsonData['command']['payload']['id'])
            ) {
                $id = $this->stringify($jsonData['command']['payload']['id']);
                if ($id !== null) {
                    return $id;
                }
            }
        }

        return null;
    }

    private function extractCollabPersonId(Request $request, ?array $jsonData): ?string
    {
        return $this->extractFromRequestOrJson($request, ['collabPersonId', 'collab_person_id'], $jsonData);
    }

    private function extractSecondFactorId(Request $request, ?array $jsonData): ?string
    {
        $route = (string) $request->attributes->get('_route');
        if (in_array($route, self::SECOND_FACTOR_ROUTES, true) && $request->attributes->has('id')) {
            $id = $this->stringify($request->attributes->get('id'));
            if ($id !== null) {
                return $id;
            }
        }

        return $this->extractFromRequestOrJson(
            $request,
            ['secondFactorId', 'second_factor_id', 'authoringSecondFactorId', 'authoring_second_factor_id'],
            $jsonData,
        );
    }

    private function extractRecoveryTokenId(Request $request, ?array $jsonData): ?string
    {
        if ($request->attributes->get('_route') === 'recovery_token' && $request->attributes->has('id')) {
            $id = $this->stringify($request->attributes->get('id'));
            if ($id !== null) {
                return $id;
            }
        }

        return $this->extractFromRequestOrJson(
            $request,
            ['recoveryTokenIdId', 'recoveryTokenId', 'recovery_token_id', 'recovery_token_id_id'],
            $jsonData,
        );
    }

    private function extractNameId(Request $request, ?array $jsonData): ?string
    {
        return $this->extractFromRequestOrJson($request, ['nameId', 'name_id', 'NameID'], $jsonData);
    }

    private function extractInstitution(Request $request, ?array $jsonData): ?string
    {
        $value = $this->extractFromRequestOrJson(
            $request,
            ['institution', 'institutionName', 'institution_name', 'raInstitution', 'ra_institution'],
            $jsonData,
        );

        if ($value !== null) {
            return $value;
        }

        if ($jsonData !== null && isset($jsonData['meta']) && is_array($jsonData['meta'])) {
            foreach (['actor_institution', 'actorInstitution', 'institution'] as $key) {
                if (isset($jsonData['meta'][$key])) {
                    $id = $this->stringify($jsonData['meta'][$key]);
                    if ($id !== null) {
                        return $id;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param list<string> $keys
     */
    private function extractFromRequestOrJson(Request $request, array $keys, ?array $jsonData): ?string
    {
        foreach ($keys as $key) {
            if ($request->attributes->has($key)) {
                $val = $this->stringify($request->attributes->get($key));
                if ($val !== null) {
                    return $val;
                }
            }
        }

        foreach ($keys as $key) {
            if ($request->query->has($key)) {
                $val = $this->stringify($request->query->get($key));
                if ($val !== null) {
                    return $val;
                }
            }
        }

        foreach ($keys as $key) {
            if ($request->request->has($key)) {
                $val = $this->stringify($request->request->get($key));
                if ($val !== null) {
                    return $val;
                }
            }
        }

        if ($jsonData !== null) {
            foreach ($keys as $key) {
                if (isset($jsonData[$key])) {
                    $val = $this->stringify($jsonData[$key]);
                    if ($val !== null) {
                        return $val;
                    }
                }
            }

            if (isset($jsonData['command']['payload']) && is_array($jsonData['command']['payload'])) {
                foreach ($keys as $key) {
                    if (isset($jsonData['command']['payload'][$key])) {
                        $val = $this->stringify($jsonData['command']['payload'][$key]);
                        if ($val !== null) {
                            return $val;
                        }
                    }
                }
            }

            if (isset($jsonData['payload']) && is_array($jsonData['payload'])) {
                foreach ($keys as $key) {
                    if (isset($jsonData['payload'][$key])) {
                        $val = $this->stringify($jsonData['payload'][$key]);
                        if ($val !== null) {
                            return $val;
                        }
                    }
                }
            }
        }

        return null;
    }

    private function stringify(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            return $trimmed !== '' ? $trimmed : null;
        }

        if (is_scalar($value) || $value instanceof Stringable) {
            $trimmed = trim((string) $value);
            return $trimmed !== '' ? $trimmed : null;
        }

        return null;
    }
}
