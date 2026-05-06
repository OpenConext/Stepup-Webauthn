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
use Surfnet\Webauthn\Entity\PublicKeyCredentialSource as SurfnetPublicKeyCredentialSource;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\CeremonyStep\CeremonyStep;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;

/**
 * First step in the request ceremony. Logs the AAGUID of the authenticating credential.
 * Warns when the credential has a null AAGUID (CTAP1/U2F legacy token).
 * Never throws — purely observational.
 */
final class LogAuthenticationData implements CeremonyStep
{
    private const NULL_AAGUID = '00000000-0000-0000-0000-000000000000';

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
        if (!$authenticatorResponse instanceof AuthenticatorAssertionResponse) {
            return;
        }

        $aaguid = $publicKeyCredentialSource->aaguid->__toString();
        $fmt = $publicKeyCredentialSource instanceof SurfnetPublicKeyCredentialSource
            ? $publicKeyCredentialSource->getFmt()
            : null;

        if ($aaguid === self::NULL_AAGUID) {
            $this->logger->warning('Authentication with legacy token: null AAGUID indicates CTAP1/U2F credential registered before FIDO MDS enforcement', [
                'aaguid' => $aaguid,
                'fmt' => $fmt,
                'userHandle' => $userHandle,
            ]);
            return;
        }

        $this->logger->info('Authentication ceremony started', [
            'aaguid' => $aaguid,
            'fmt' => $fmt,
            'userHandle' => $userHandle,
        ]);
    }
}
