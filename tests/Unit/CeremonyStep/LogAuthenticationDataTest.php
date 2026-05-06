<?php

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

declare(strict_types=1);

namespace Test\Unit\CeremonyStep;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Surfnet\Webauthn\CeremonyStep\LogAuthenticationData;
use Symfony\Component\Uid\Uuid;
use Webauthn\AttestedCredentialData;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\PublicKeyCredentialSource;

class LogAuthenticationDataTest extends AbstractCeremonyStepTestCase
{
    private LogAuthenticationData $step;

    protected function setUp(): void
    {
        parent::setUp();
        $this->step = new LogAuthenticationData(new NullLogger());
    }

    public function test_skips_attestation_response_without_logging(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('info');
        $logger->expects($this->never())->method('warning');

        $step = new LogAuthenticationData($logger);
        $step->process(
            $this->credentialSource,
            $this->buildAttestationResponse($this->makeAttestedCredentialData()),
            $this->options,
            null,
            'example.com'
        );
    }

    public function test_logs_info_for_known_aaguid(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info');
        $logger->expects($this->never())->method('warning');

        $credentialSource = $this->credentialSourceWithAaguid('550e8400-e29b-41d4-a716-446655440000');

        $step = new LogAuthenticationData($logger);
        $step->process(
            $credentialSource,
            $this->createMock(AuthenticatorAssertionResponse::class),
            $this->options,
            null,
            'example.com'
        );
    }

    public function test_logs_warning_for_null_aaguid(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('info');
        $logger->expects($this->once())->method('warning');

        $credentialSource = $this->credentialSourceWithAaguid('00000000-0000-0000-0000-000000000000');

        $step = new LogAuthenticationData($logger);
        $step->process(
            $credentialSource,
            $this->createMock(AuthenticatorAssertionResponse::class),
            $this->options,
            null,
            'example.com'
        );
    }

    public function test_never_throws(): void
    {
        $credentialSource = $this->credentialSourceWithAaguid('00000000-0000-0000-0000-000000000000');

        $this->step->process(
            $credentialSource,
            $this->createMock(AuthenticatorAssertionResponse::class),
            $this->options,
            null,
            'example.com'
        );

        $this->addToAssertionCount(1);
    }

    private function credentialSourceWithAaguid(string $uuid): PublicKeyCredentialSource
    {
        return PublicKeyCredentialSource::create(
            'credential-id',
            'public-key',
            [],
            'basic',
            new \Webauthn\TrustPath\EmptyTrustPath(),
            Uuid::fromString($uuid),
            'public-key-bytes',
            'user-handle',
            0,
        );
    }
}
