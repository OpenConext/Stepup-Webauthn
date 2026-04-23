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

use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\CeremonyStep\CeremonyStep;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\MetadataService\Statement\AuthenticatorStatus;
use Webauthn\MetadataService\Statement\StatusReport;
use Webauthn\MetadataService\StatusReportRepository;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;

final class CheckFidoCertified implements CeremonyStep
{
    private const FIDO_CERTIFIED_STATUSES = [
        AuthenticatorStatus::FIDO_CERTIFIED,
        AuthenticatorStatus::FIDO_CERTIFIED_L1,
        AuthenticatorStatus::FIDO_CERTIFIED_L1plus,
        AuthenticatorStatus::FIDO_CERTIFIED_L2,
        AuthenticatorStatus::FIDO_CERTIFIED_L2plus,
        AuthenticatorStatus::FIDO_CERTIFIED_L3,
        AuthenticatorStatus::FIDO_CERTIFIED_L3plus,
        AuthenticatorStatus::FIDO_CERTIFIED_L4,
        AuthenticatorStatus::FIDO_CERTIFIED_L5,
    ];

    public function __construct(
        private readonly StatusReportRepository $statusReportRepository,
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

        $aaguid = $attestedCredentialData->aaguid->__toString();
        $reports = $this->statusReportRepository->findStatusReportsByAAGUID($aaguid);

        if ($reports === []) {
            throw AuthenticatorResponseVerificationException::create(
                sprintf('No status reports found for authenticator with AAGUID "%s".', $aaguid)
            );
        }

        $mostRecent = $this->mostRecentReport($reports);

        if (!in_array($mostRecent->status, self::FIDO_CERTIFIED_STATUSES, true)) {
            throw AuthenticatorResponseVerificationException::create(
                sprintf(
                    'Authenticator is not FIDO certified. Most recent status: "%s".',
                    $mostRecent->status
                )
            );
        }
    }

    /**
     * @param StatusReport[] $reports
     */
    private function mostRecentReport(array $reports): StatusReport
    {
        assert($reports !== []);
        // Tie-break: equal dates keep the later array index (stable for FIDO MDS, one report per event).
        return array_reduce(
            $reports,
            fn(StatusReport $carry, StatusReport $report): StatusReport =>
                ($report->effectiveDate ?? '') >= ($carry->effectiveDate ?? '') ? $report : $carry,
            $reports[0]
        );
    }
}
