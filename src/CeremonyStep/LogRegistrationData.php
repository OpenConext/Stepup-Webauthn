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
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\CeremonyStep\CeremonyStep;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;

/**
 * First step in the creation ceremony. Logs the raw attestation data for audit/debugging purposes.
 * Never throws — purely observational.
 */
final class LogRegistrationData implements CeremonyStep
{
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

        $registrationId = sodium_bin2base64($publicKeyCredentialOptions->challenge, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);

        $attestedCredentialData = $authenticatorResponse->attestationObject->authData->attestedCredentialData;
        $aaguid = $attestedCredentialData?->aaguid->__toString() ?? '00000000-0000-0000-0000-000000000000';

        $this->logger->info('Registration ceremony started', [
            'registrationId' => $registrationId,
            'aaguid' => $aaguid,
            'attestationFormat' => $authenticatorResponse->attestationObject->attStmt->fmt,
            'clientDataType' => $authenticatorResponse->clientDataJSON->type,
            'origin' => $authenticatorResponse->clientDataJSON->origin,
        ]);

        $this->logger->info('Registration ceremony raw attestation data', [
            'registrationId' => $registrationId,
            'clientDataJSON' => $authenticatorResponse->clientDataJSON->rawData,
            'attestationObject' => sodium_bin2base64(
                $authenticatorResponse->attestationObject->rawAttestationObject,
                SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING
            ),
        ]);
    }
}
