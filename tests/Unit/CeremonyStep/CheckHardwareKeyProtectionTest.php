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
        $this->step = new CheckHardwareKeyProtection($this->repository);
    }

    public function test_skips_assertion_response(): void
    {
        $this->repository->expects($this->never())->method('findOneByAAGUID');

        $this->step->process(
            $this->credentialSource,
            $this->createMock(AuthenticatorAssertionResponse::class),
            $this->options,
            null,
            'example.com'
        );

        $this->addToAssertionCount(1);
    }

    public function test_skips_when_no_attested_credential_data(): void
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

    public function test_skips_when_no_metadata_statement(): void
    {
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

    public function test_passes_for_hardware_key_protection(): void
    {
        $metadata = $this->createMock(MetadataStatement::class);
        $metadata->keyProtection = [MetadataStatement::KEY_PROTECTION_HARDWARE];
        $this->repository->method('findOneByAAGUID')->willReturn($metadata);

        $this->step->process(
            $this->credentialSource,
            $this->buildAttestationResponse($this->makeAttestedCredentialData()),
            $this->options,
            null,
            'example.com'
        );

        $this->addToAssertionCount(1);
    }

    public function test_passes_for_secure_element_key_protection(): void
    {
        $metadata = $this->createMock(MetadataStatement::class);
        $metadata->keyProtection = [MetadataStatement::KEY_PROTECTION_SECURE_ELEMENT];
        $this->repository->method('findOneByAAGUID')->willReturn($metadata);

        $this->step->process(
            $this->credentialSource,
            $this->buildAttestationResponse($this->makeAttestedCredentialData()),
            $this->options,
            null,
            'example.com'
        );

        $this->addToAssertionCount(1);
    }

    public function test_throws_for_software_key_protection(): void
    {
        $metadata = $this->createMock(MetadataStatement::class);
        $metadata->keyProtection = [MetadataStatement::KEY_PROTECTION_SOFTWARE];
        $this->repository->method('findOneByAAGUID')->willReturn($metadata);

        $this->expectException(AuthenticatorResponseVerificationException::class);

        $this->step->process(
            $this->credentialSource,
            $this->buildAttestationResponse($this->makeAttestedCredentialData()),
            $this->options,
            null,
            'example.com'
        );
    }

    public function test_throws_for_tee_key_protection(): void
    {
        $metadata = $this->createMock(MetadataStatement::class);
        $metadata->keyProtection = [MetadataStatement::KEY_PROTECTION_TEE];
        $this->repository->method('findOneByAAGUID')->willReturn($metadata);

        $this->expectException(AuthenticatorResponseVerificationException::class);

        $this->step->process(
            $this->credentialSource,
            $this->buildAttestationResponse($this->makeAttestedCredentialData()),
            $this->options,
            null,
            'example.com'
        );
    }

    public function test_throws_for_empty_key_protection(): void
    {
        $metadata = $this->createMock(MetadataStatement::class);
        $metadata->keyProtection = [];
        $this->repository->method('findOneByAAGUID')->willReturn($metadata);

        $this->expectException(AuthenticatorResponseVerificationException::class);

        $this->step->process(
            $this->credentialSource,
            $this->buildAttestationResponse($this->makeAttestedCredentialData()),
            $this->options,
            null,
            'example.com'
        );
    }
}
