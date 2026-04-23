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

use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Webauthn\AttestationStatement\AttestationObject;
use Webauthn\AttestationStatement\AttestationStatement;
use Webauthn\AttestedCredentialData;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorData;
use Webauthn\CollectedClientData;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\TrustPath\EmptyTrustPath;

abstract class AbstractCeremonyStepTestCase extends TestCase
{
    protected PublicKeyCredentialSource $credentialSource;
    protected PublicKeyCredentialRequestOptions $options;

    protected function setUp(): void
    {
        $this->credentialSource = $this->createMock(PublicKeyCredentialSource::class);
        $this->options = PublicKeyCredentialRequestOptions::create('challenge');
    }

    protected function makeAttestedCredentialData(): AttestedCredentialData
    {
        return AttestedCredentialData::create(
            Uuid::fromString('00000000-0000-0000-0000-000000000000'),
            'credential-id',
            null
        );
    }

    protected function buildAttestationResponse(
        ?AttestedCredentialData $attestedCredentialData = null,
        string $attStmtType = AttestationStatement::TYPE_BASIC,
        bool $backupEligible = false
    ): AuthenticatorAttestationResponse {
        $flags = AuthenticatorData::FLAG_UP;
        if ($backupEligible) {
            $flags |= AuthenticatorData::FLAG_BE;
        }
        if ($attestedCredentialData !== null) {
            $flags |= AuthenticatorData::FLAG_AT;
        }

        $attStmt = AttestationStatement::create('packed', [], $attStmtType, EmptyTrustPath::create());
        $authData = AuthenticatorData::create(str_repeat("\x00", 37), str_repeat("\x00", 32), chr($flags), 0, $attestedCredentialData);
        $attestationObject = AttestationObject::create('raw', $attStmt, $authData);
        $clientData = CollectedClientData::create('raw', ['type' => 'webauthn.create', 'challenge' => 'dGVzdA', 'origin' => 'https://example.com']);

        return new AuthenticatorAttestationResponse($clientData, $attestationObject);
    }
}
