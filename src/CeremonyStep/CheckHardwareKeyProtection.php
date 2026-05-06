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
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\MetadataService\MetadataStatementRepository;
use Webauthn\MetadataService\Statement\MetadataStatement;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;

final class CheckHardwareKeyProtection implements CeremonyStep
{
    private const ALLOWED_KEY_PROTECTION = [
        MetadataStatement::KEY_PROTECTION_HARDWARE,
        MetadataStatement::KEY_PROTECTION_SECURE_ELEMENT,
    ];

    public function __construct(
        private readonly MetadataStatementRepository $metadataStatementRepository,
        private readonly LoggerInterface $logger,
    ) {
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

        $attestedCredentialData = $authenticatorResponse->attestationObject->authData->attestedCredentialData;
        if ($attestedCredentialData === null) {
            return;
        }

        $registrationId = sodium_bin2base64($publicKeyCredentialOptions->challenge, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
        $aaguid = $attestedCredentialData->aaguid->__toString();
        $metadataStatement = $this->metadataStatementRepository->findOneByAAGUID($aaguid);

        // No MDS entry means we cannot verify key protection for this AAGUID.
        // TYPE_NONE attestation is already rejected by CheckAttestationIsNotNone,
        // so this only affects authenticators that attest but are absent from the FIDO MDS.
        if ($metadataStatement === null) {
            $this->logger->info('No MDS metadata statement found for AAGUID, skipping key protection check', [
                'registrationId' => $registrationId,
                'aaguid' => $aaguid,
            ]);
            return;
        }

        $this->logger->info('MDS metadata statement found for authenticator', [
            'registrationId' => $registrationId,
            'aaguid' => $aaguid,
            'description' => isset($metadataStatement->description) ? $metadataStatement->description : null,
            'keyProtection' => $metadataStatement->keyProtection,
        ]);

        if (count(array_intersect($metadataStatement->keyProtection, self::ALLOWED_KEY_PROTECTION)) === 0) {
            throw AuthenticatorResponseVerificationException::create(
                sprintf(
                    'Authenticator key is not hardware-bound. keyProtection: [%s]',
                    implode(', ', $metadataStatement->keyProtection)
                )
            );
        }

        $this->logger->info('Key protection check passed', ['registrationId' => $registrationId]);
    }
}
