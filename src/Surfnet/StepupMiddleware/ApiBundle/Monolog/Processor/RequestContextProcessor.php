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
use function array_filter;
use function in_array;
use function is_array;
use function is_scalar;
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
        $jsonData = $this->parseJsonContent($request->getContent());
        $sources = $this->collectSources($request, $jsonData);

        $fields = [
            'identity_id' => $this->extractIdentityId($request, $sources, $jsonData),
            'collab_person_id' => $this->extractFromSources($sources, ['collabPersonId', 'collab_person_id']),
            'second_factor_id' => $this->extractSecondFactorId($request, $sources),
            'recovery_token_id' => $this->extractRecoveryTokenId($request, $sources),
            'name_id' => $this->extractFromSources($sources, ['nameId', 'name_id', 'NameID']),
            'institution' => $this->extractInstitution($sources, $jsonData),
        ];

        return array_filter($fields, static fn (?string $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseJsonContent(string $content): ?array
    {
        if ($content === '') {
            return null;
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array<string, mixed>|null $jsonData
     * @return list<array<string, mixed>>
     */
    private function collectSources(Request $request, ?array $jsonData): array
    {
        $sources = [
            $request->attributes->all(),
            $request->query->all(),
            $request->request->all(),
        ];

        if ($jsonData === null) {
            return $sources;
        }

        $sources[] = $jsonData;

        if (isset($jsonData['command']['payload']) && is_array($jsonData['command']['payload'])) {
            $sources[] = $jsonData['command']['payload'];
        }

        if (isset($jsonData['payload']) && is_array($jsonData['payload'])) {
            $sources[] = $jsonData['payload'];
        }

        return $sources;
    }

    /**
     * @param list<array<string, mixed>> $sources
     * @param array<string, mixed>|null $jsonData
     */
    private function extractIdentityId(Request $request, array $sources, ?array $jsonData): ?string
    {
        return $this->extractRouteId($request, ['identity'])
            ?? $this->extractFromSources($sources, ['identityId', 'identity_id'])
            ?? $this->extractIdentityCommandId($jsonData);
    }

    /**
     * @param list<string> $allowedRoutes
     */
    private function extractRouteId(Request $request, array $allowedRoutes): ?string
    {
        $route = (string) $request->attributes->get('_route');
        if (in_array($route, $allowedRoutes, true)) {
            return $this->stringify($request->attributes->get('id'));
        }

        return null;
    }

    /**
     * @param array<string, mixed>|null $jsonData
     */
    private function extractIdentityCommandId(?array $jsonData): ?string
    {
        $commandName = $jsonData['command']['name'] ?? null;
        if (in_array($commandName, ['Identity:CreateIdentity', 'Identity:UpdateIdentity'], true)) {
            return $this->stringify($jsonData['command']['payload']['id'] ?? null);
        }

        return null;
    }

    /**
     * @param list<array<string, mixed>> $sources
     */
    private function extractSecondFactorId(Request $request, array $sources): ?string
    {
        return $this->extractRouteId($request, self::SECOND_FACTOR_ROUTES)
            ?? $this->extractFromSources(
                $sources,
                ['secondFactorId', 'second_factor_id', 'authoringSecondFactorId', 'authoring_second_factor_id'],
            );
    }

    /**
     * @param list<array<string, mixed>> $sources
     */
    private function extractRecoveryTokenId(Request $request, array $sources): ?string
    {
        return $this->extractRouteId($request, ['recovery_token'])
            ?? $this->extractFromSources(
                $sources,
                ['recoveryTokenIdId', 'recoveryTokenId', 'recovery_token_id', 'recovery_token_id_id'],
            );
    }

    /**
     * @param list<array<string, mixed>> $sources
     * @param array<string, mixed>|null $jsonData
     */
    private function extractInstitution(array $sources, ?array $jsonData): ?string
    {
        return $this->extractFromSources(
            $sources,
            ['institution', 'institutionName', 'institution_name', 'raInstitution', 'ra_institution'],
        ) ?? $this->extractMetaInstitution($jsonData);
    }

    /**
     * @param array<string, mixed>|null $jsonData
     */
    private function extractMetaInstitution(?array $jsonData): ?string
    {
        $meta = $jsonData['meta'] ?? null;
        if (!is_array($meta)) {
            return null;
        }

        return $this->extractFromSources([$meta], ['actor_institution', 'actorInstitution', 'institution']);
    }

    /**
     * @param list<array<string, mixed>> $sources
     * @param list<string> $keys
     */
    private function extractFromSources(array $sources, array $keys): ?string
    {
        foreach ($sources as $source) {
            foreach ($keys as $key) {
                if (isset($source[$key])) {
                    $val = $this->stringify($source[$key]);
                    if ($val !== null) {
                        return $val;
                    }
                }
            }
        }

        return null;
    }

    private function stringify(mixed $value): ?string
    {
        if (is_scalar($value) || $value instanceof Stringable) {
            $trimmed = trim((string) $value);

            return $trimmed !== '' ? $trimmed : null;
        }

        return null;
    }
}
