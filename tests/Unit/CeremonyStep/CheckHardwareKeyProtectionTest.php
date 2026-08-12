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

use Psr\Log\NullLogger;
use Surfnet\Webauthn\CeremonyStep\CheckHardwareKeyProtection;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\MetadataService\MetadataStatementRepository;
use Webauthn\MetadataService\Statement\MetadataStatement;

class CheckHardwareKeyProtectionTest extends AbstractCeremonyStepTestCase
{
    private MetadataStatementRepository $repository;
    private CheckHardwareKeyProtection $step;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(MetadataStatementRepository::class);
        $this->step = new CheckHardwareKeyProtection($this->repository, new NullLogger());
    }

    public function testSkipsAssertionResponse(): void
    {
        $this->repository->expects($this->never())->method('findOneByAAGUID');

        $this->step->process(
            $this->credentialSource,
            $this->createStub(AuthenticatorAssertionResponse::class),
            $this->options,
            null,
            'example.com'
        );

        $this->addToAssertionCount(1);
    }

    public function testSkipsWhenNoAttestedCredentialData(): void
    {
        $this->repository->expects($this->never())->method('findOneByAAGUID');

        $this->step->process(
            $this->credentialSource,
            $this->buildAttestationResponse(),
            $this->options,
            null,
            'example.com'
        );

        $this->addToAssertionCount(1);
    }

    public function testSkipsWhenNoMetadataStatement(): void
    {
        // Authenticators absent from the MDS cannot have their key protection verified here.
        // CheckFidoCertified is the safety net: it throws when no status reports exist for the AAGUID.
        $this->repository->method('findOneByAAGUID')->willReturn(null);

        $this->step->process(
            $this->credentialSource,
            $this->buildAttestationResponse($this->makeAttestedCredentialData()),
            $this->options,
            null,
            'example.com'
        );

        $this->addToAssertionCount(1);
    }

    public function testPassesForHardwareKeyProtection(): void
    {
        $this->repository->method('findOneByAAGUID')->willReturn(
            $this->makeMetadata([MetadataStatement::KEY_PROTECTION_HARDWARE])
        );

        $this->step->process(
            $this->credentialSource,
            $this->buildAttestationResponse($this->makeAttestedCredentialData()),
            $this->options,
            null,
            'example.com'
        );

        $this->addToAssertionCount(1);
    }

    public function testPassesForSecureElementKeyProtection(): void
    {
        $this->repository->method('findOneByAAGUID')->willReturn(
            $this->makeMetadata([MetadataStatement::KEY_PROTECTION_SECURE_ELEMENT])
        );

        $this->step->process(
            $this->credentialSource,
            $this->buildAttestationResponse($this->makeAttestedCredentialData()),
            $this->options,
            null,
            'example.com'
        );

        $this->addToAssertionCount(1);
    }

    public function testThrowsForSoftwareKeyProtection(): void
    {
        $this->repository->method('findOneByAAGUID')->willReturn(
            $this->makeMetadata([MetadataStatement::KEY_PROTECTION_SOFTWARE])
        );

        $this->expectException(AuthenticatorResponseVerificationException::class);

        $this->step->process(
            $this->credentialSource,
            $this->buildAttestationResponse($this->makeAttestedCredentialData()),
            $this->options,
            null,
            'example.com'
        );
    }

    public function testThrowsForTeeKeyProtection(): void
    {
        $this->repository->method('findOneByAAGUID')->willReturn(
            $this->makeMetadata([MetadataStatement::KEY_PROTECTION_TEE])
        );

        $this->expectException(AuthenticatorResponseVerificationException::class);

        $this->step->process(
            $this->credentialSource,
            $this->buildAttestationResponse($this->makeAttestedCredentialData()),
            $this->options,
            null,
            'example.com'
        );
    }

    public function testThrowsForEmptyKeyProtection(): void
    {
        $this->repository->method('findOneByAAGUID')->willReturn(
            $this->makeMetadata([])
        );

        $this->expectException(AuthenticatorResponseVerificationException::class);

        $this->step->process(
            $this->credentialSource,
            $this->buildAttestationResponse($this->makeAttestedCredentialData()),
            $this->options,
            null,
            'example.com'
        );
    }

    /** @param string[] $keyProtection */
    private function makeMetadata(array $keyProtection): MetadataStatement
    {
        return MetadataStatement::create(
            'Test Authenticator',
            1,
            'fido2',
            3,
            [],
            [],
            [],
            ['basic_full'],
            [],
            [],
            [],
            [],
            keyProtection: $keyProtection,
        );
    }
}
