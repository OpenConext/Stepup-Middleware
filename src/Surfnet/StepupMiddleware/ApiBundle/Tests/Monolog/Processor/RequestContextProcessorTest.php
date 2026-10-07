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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Monolog\Processor;

use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Surfnet\StepupMiddleware\ApiBundle\Monolog\Processor\RequestContextProcessor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use function json_encode;

class RequestContextProcessorTest extends TestCase
{
    private function createLogRecord(array $extra = []): LogRecord
    {
        return new LogRecord(
            datetime: new DateTimeImmutable(),
            channel: 'app',
            level: Level::Info,
            message: 'Test log message',
            context: [],
            extra: $extra,
        );
    }

    #[Test]
    #[Group('api-bundle')]
    public function it_returns_record_unmodified_when_no_request_is_active(): void
    {
        $requestStack = new RequestStack();
        $processor = new RequestContextProcessor($requestStack);

        $record = $this->createLogRecord(['existing' => 'data']);
        $processed = $processor($record);

        $this->assertSame(['existing' => 'data'], $processed->extra);
    }

    #[Test]
    #[Group('api-bundle')]
    public function it_extracts_identity_id_from_route_attributes(): void
    {
        $request = new Request();
        $request->attributes->set('identityId', 'abc-123-route');

        $requestStack = new RequestStack();
        $requestStack->push($request);
        $processor = new RequestContextProcessor($requestStack);

        $record = $this->createLogRecord();
        $processed = $processor($record);

        $this->assertArrayHasKey('identity_id', $processed->extra);
        $this->assertSame('abc-123-route', $processed->extra['identity_id']);
    }

    #[Test]
    #[Group('api-bundle')]
    public function it_extracts_snake_case_identity_id_from_route_attributes(): void
    {
        $request = new Request();
        $request->attributes->set('identity_id', 'abc-123-snake-route');

        $requestStack = new RequestStack();
        $requestStack->push($request);
        $processor = new RequestContextProcessor($requestStack);

        $record = $this->createLogRecord();
        $processed = $processor($record);

        $this->assertArrayHasKey('identity_id', $processed->extra);
        $this->assertSame('abc-123-snake-route', $processed->extra['identity_id']);
    }

    #[Test]
    #[Group('api-bundle')]
    public function it_extracts_identity_id_from_identity_route_with_id_attribute(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'identity');
        $request->attributes->set('id', 'abc-123-identity-get');

        $requestStack = new RequestStack();
        $requestStack->push($request);
        $processor = new RequestContextProcessor($requestStack);

        $record = $this->createLogRecord();
        $processed = $processor($record);

        $this->assertArrayHasKey('identity_id', $processed->extra);
        $this->assertSame('abc-123-identity-get', $processed->extra['identity_id']);
    }

    #[Test]
    #[Group('api-bundle')]
    public function it_extracts_identity_id_from_query_parameters(): void
    {
        $request = new Request(query: ['identityId' => 'abc-123-query']);

        $requestStack = new RequestStack();
        $requestStack->push($request);
        $processor = new RequestContextProcessor($requestStack);

        $record = $this->createLogRecord();
        $processed = $processor($record);

        $this->assertArrayHasKey('identity_id', $processed->extra);
        $this->assertSame('abc-123-query', $processed->extra['identity_id']);
    }

    #[Test]
    #[Group('api-bundle')]
    public function it_extracts_snake_case_identity_id_from_query_parameters(): void
    {
        $request = new Request(query: ['identity_id' => 'abc-123-snake-query']);

        $requestStack = new RequestStack();
        $requestStack->push($request);
        $processor = new RequestContextProcessor($requestStack);

        $record = $this->createLogRecord();
        $processed = $processor($record);

        $this->assertArrayHasKey('identity_id', $processed->extra);
        $this->assertSame('abc-123-snake-query', $processed->extra['identity_id']);
    }

    #[Test]
    #[Group('api-bundle')]
    public function it_extracts_identity_id_from_request_body_post_parameters(): void
    {
        $request = new Request(request: ['identityId' => 'abc-123-body']);

        $requestStack = new RequestStack();
        $requestStack->push($request);
        $processor = new RequestContextProcessor($requestStack);

        $record = $this->createLogRecord();
        $processed = $processor($record);

        $this->assertArrayHasKey('identity_id', $processed->extra);
        $this->assertSame('abc-123-body', $processed->extra['identity_id']);
    }

    #[Test]
    #[Group('api-bundle')]
    public function it_extracts_identity_id_from_command_json_payload(): void
    {
        $payload = [
            'meta' => ['actor_id' => 'actor-uuid'],
            'command' => [
                'name' => 'Identity:VetSecondFactor',
                'uuid' => 'command-uuid',
                'payload' => [
                    'identityId' => 'abc-123-command-payload',
                ],
            ],
        ];

        $request = new Request(
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload),
        );

        $requestStack = new RequestStack();
        $requestStack->push($request);
        $processor = new RequestContextProcessor($requestStack);

        $record = $this->createLogRecord();
        $processed = $processor($record);

        $this->assertArrayHasKey('identity_id', $processed->extra);
        $this->assertSame('abc-123-command-payload', $processed->extra['identity_id']);
    }

    #[Test]
    #[Group('api-bundle')]
    public function it_extracts_snake_case_identity_id_from_command_json_payload(): void
    {
        $payload = [
            'meta' => ['actor_id' => 'actor-uuid'],
            'command' => [
                'name' => 'Identity:VetSecondFactor',
                'uuid' => 'command-uuid',
                'payload' => [
                    'identity_id' => 'abc-123-command-snake-payload',
                ],
            ],
        ];

        $request = new Request(
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload),
        );

        $requestStack = new RequestStack();
        $requestStack->push($request);
        $processor = new RequestContextProcessor($requestStack);

        $record = $this->createLogRecord();
        $processed = $processor($record);

        $this->assertArrayHasKey('identity_id', $processed->extra);
        $this->assertSame('abc-123-command-snake-payload', $processed->extra['identity_id']);
    }

    #[Test]
    #[Group('api-bundle')]
    public function it_extracts_id_from_create_identity_command_json_payload(): void
    {
        $payload = [
            'meta' => ['actor_id' => 'actor-uuid'],
            'command' => [
                'name' => 'Identity:CreateIdentity',
                'uuid' => 'command-uuid',
                'payload' => [
                    'id' => 'abc-123-created-identity',
                ],
            ],
        ];

        $request = new Request(
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload),
        );

        $requestStack = new RequestStack();
        $requestStack->push($request);
        $processor = new RequestContextProcessor($requestStack);

        $record = $this->createLogRecord();
        $processed = $processor($record);

        $this->assertArrayHasKey('identity_id', $processed->extra);
        $this->assertSame('abc-123-created-identity', $processed->extra['identity_id']);
    }

    #[Test]
    #[Group('api-bundle')]
    public function it_extracts_identity_id_from_root_json_payload(): void
    {
        $payload = [
            'identityId' => 'abc-123-root-json',
        ];

        $request = new Request(
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload),
        );

        $requestStack = new RequestStack();
        $requestStack->push($request);
        $processor = new RequestContextProcessor($requestStack);

        $record = $this->createLogRecord();
        $processed = $processor($record);

        $this->assertArrayHasKey('identity_id', $processed->extra);
        $this->assertSame('abc-123-root-json', $processed->extra['identity_id']);
    }

    #[Test]
    #[Group('api-bundle')]
    public function it_extracts_collab_person_id_from_route_and_query(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'deprovision');
        $request->attributes->set('collabPersonId', 'urn:collab:person:surfnet.nl:jdoe');

        $requestStack = new RequestStack();
        $requestStack->push($request);
        $processor = new RequestContextProcessor($requestStack);

        $record = $this->createLogRecord();
        $processed = $processor($record);

        $this->assertArrayHasKey('collab_person_id', $processed->extra);
        $this->assertSame('urn:collab:person:surfnet.nl:jdoe', $processed->extra['collab_person_id']);
    }

    #[Test]
    #[Group('api-bundle')]
    public function it_extracts_second_factor_id_from_route_id_query_and_payload(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'verified_second_factor');
        $request->attributes->set('id', 'sf-uuid-123');

        $requestStack = new RequestStack();
        $requestStack->push($request);
        $processor = new RequestContextProcessor($requestStack);

        $record = $this->createLogRecord();
        $processed = $processor($record);

        $this->assertArrayHasKey('second_factor_id', $processed->extra);
        $this->assertSame('sf-uuid-123', $processed->extra['second_factor_id']);

        $payload = [
            'command' => [
                'name' => 'Identity:VetSecondFactor',
                'uuid' => 'cmd-1',
                'payload' => [
                    'secondFactorId' => 'sf-payload-456',
                ],
            ],
        ];
        $request2 = new Request(
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload),
        );
        $requestStack2 = new RequestStack();
        $requestStack2->push($request2);
        $processor2 = new RequestContextProcessor($requestStack2);
        $processed2 = $processor2($this->createLogRecord());
        $this->assertSame('sf-payload-456', $processed2->extra['second_factor_id']);
    }

    #[Test]
    #[Group('api-bundle')]
    public function it_extracts_recovery_token_id_from_route_id_and_recovery_token_id_id(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'recovery_token');
        $request->attributes->set('id', 'recovery-token-uuid-1');

        $requestStack = new RequestStack();
        $requestStack->push($request);
        $processor = new RequestContextProcessor($requestStack);

        $record = $this->createLogRecord();
        $processed = $processor($record);

        $this->assertArrayHasKey('recovery_token_id', $processed->extra);
        $this->assertSame('recovery-token-uuid-1', $processed->extra['recovery_token_id']);

        $request2 = new Request(query: ['recoveryTokenIdId' => 'recovery-token-uuid-2']);
        $requestStack2 = new RequestStack();
        $requestStack2->push($request2);
        $processor2 = new RequestContextProcessor($requestStack2);
        $processed2 = $processor2($this->createLogRecord());
        $this->assertSame('recovery-token-uuid-2', $processed2->extra['recovery_token_id']);
    }

    #[Test]
    #[Group('api-bundle')]
    public function it_extracts_name_id_from_route_query_and_payload(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'identity_sraa_get');
        $request->attributes->set('nameId', 'urn:collab:person:surfnet.nl:nameid1');

        $requestStack = new RequestStack();
        $requestStack->push($request);
        $processor = new RequestContextProcessor($requestStack);

        $record = $this->createLogRecord();
        $processed = $processor($record);

        $this->assertArrayHasKey('name_id', $processed->extra);
        $this->assertSame('urn:collab:person:surfnet.nl:nameid1', $processed->extra['name_id']);

        $request2 = new Request(query: ['NameID' => 'query-name-id']);
        $requestStack2 = new RequestStack();
        $requestStack2->push($request2);
        $processor2 = new RequestContextProcessor($requestStack2);
        $processed2 = $processor2($this->createLogRecord());
        $this->assertSame('query-name-id', $processed2->extra['name_id']);
    }

    #[Test]
    #[Group('api-bundle')]
    public function it_extracts_institution_from_query_route_and_meta(): void
    {
        $request = new Request(query: ['institution' => 'SURFnet']);
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $processor = new RequestContextProcessor($requestStack);

        $record = $this->createLogRecord();
        $processed = $processor($record);

        $this->assertArrayHasKey('institution', $processed->extra);
        $this->assertSame('SURFnet', $processed->extra['institution']);

        $payload = [
            'meta' => [
                'actor_institution' => 'Institution-From-Meta',
            ],
            'command' => [
                'name' => 'Identity:SomeCommand',
                'uuid' => 'cmd-1',
                'payload' => [],
            ],
        ];
        $request2 = new Request(
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload),
        );
        $requestStack2 = new RequestStack();
        $requestStack2->push($request2);
        $processor2 = new RequestContextProcessor($requestStack2);
        $processed2 = $processor2($this->createLogRecord());
        $this->assertSame('Institution-From-Meta', $processed2->extra['institution']);
    }

    #[Test]
    #[Group('api-bundle')]
    public function it_extracts_multiple_context_fields_simultaneously(): void
    {
        $payload = [
            'meta' => [
                'actor_institution' => 'SURFnet',
            ],
            'command' => [
                'name' => 'Identity:CreateIdentity',
                'uuid' => 'cmd-uuid',
                'payload' => [
                    'id' => 'identity-uuid-1',
                    'name_id' => 'name-id-1',
                    'institution' => 'SURFnet-Payload',
                ],
            ],
        ];

        $request = new Request(
            query: ['secondFactorId' => 'sf-uuid-9'],
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload),
        );

        $requestStack = new RequestStack();
        $requestStack->push($request);
        $processor = new RequestContextProcessor($requestStack);

        $record = $this->createLogRecord();
        $processed = $processor($record);

        $this->assertSame('identity-uuid-1', $processed->extra['identity_id']);
        $this->assertSame('name-id-1', $processed->extra['name_id']);
        $this->assertSame('SURFnet-Payload', $processed->extra['institution']);
        $this->assertSame('sf-uuid-9', $processed->extra['second_factor_id']);
    }

    #[Test]
    #[Group('api-bundle')]
    public function it_does_not_modify_record_when_no_context_is_present(): void
    {
        $request = new Request(query: ['unrelated' => 'value']);

        $requestStack = new RequestStack();
        $requestStack->push($request);
        $processor = new RequestContextProcessor($requestStack);

        $record = $this->createLogRecord(['existing' => 'val']);
        $processed = $processor($record);

        $this->assertSame(['existing' => 'val'], $processed->extra);
    }

    #[Test]
    #[Group('api-bundle')]
    public function it_ignores_empty_identity_id(): void
    {
        $request = new Request(query: ['identityId' => '   ']);

        $requestStack = new RequestStack();
        $requestStack->push($request);
        $processor = new RequestContextProcessor($requestStack);

        $record = $this->createLogRecord();
        $processed = $processor($record);

        $this->assertArrayNotHasKey('identity_id', $processed->extra);
    }
}
