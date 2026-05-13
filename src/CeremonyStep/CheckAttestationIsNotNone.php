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

namespace Surfnet\Webauthn\CeremonyStep;

use Psr\Log\LoggerInterface;
use Webauthn\AttestationStatement\AttestationStatement;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\CeremonyStep\CeremonyStep;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;

final class CheckAttestationIsNotNone implements CeremonyStep
{
    use RegistrationIdFromChallenge;
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function process(
        PublicKeyCredentialSource $publicKeyCredentialSource,
        AuthenticatorAssertionResponse|AuthenticatorAttestationResponse $authenticatorResponse,
        PublicKeyCredentialRequestOptions|PublicKeyCredentialCreationOptions $publicKeyCredentialOptions,
        ?string $userHandle,
        string $host
    ): void {
        if (!$authenticatorResponse instanceof AuthenticatorAttestationResponse) {
            return;
        }

        $registrationId = $this->registrationId($publicKeyCredentialOptions->challenge);
        $attestationType = $authenticatorResponse->attestationObject->attStmt->type;

        $this->logger->info('Checking attestation type is not none', [
            'registrationId' => $registrationId,
            'attestationType' => $attestationType,
        ]);

        if ($attestationType === AttestationStatement::TYPE_NONE) {
            throw AuthenticatorResponseVerificationException::create(
                'Attestation is required. TYPE_NONE responses are not accepted.'
            );
        }

        $this->logger->info('Attestation type accepted', ['registrationId' => $registrationId]);
    }
}
