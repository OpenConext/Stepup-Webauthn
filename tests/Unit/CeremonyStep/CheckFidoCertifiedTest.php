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
use Surfnet\Webauthn\CeremonyStep\CheckFidoCertified;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\MetadataService\Statement\AuthenticatorStatus;
use Webauthn\MetadataService\Statement\StatusReport;
use Webauthn\MetadataService\StatusReportRepository;

class CheckFidoCertifiedTest extends AbstractCeremonyStepTestCase
{
    private StatusReportRepository $repository;
    private CheckFidoCertified $step;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(StatusReportRepository::class);
        $this->step = new CheckFidoCertified($this->repository, new NullLogger());
    }

    public function test_skips_assertion_response(): void
    {
        $this->repository->expects($this->never())->method('findStatusReportsByAAGUID');

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
        $this->repository->expects($this->never())->method('findStatusReportsByAAGUID');

        $this->step->process(
            $this->credentialSource,
            $this->buildAttestationResponse(),
            $this->options,
            null,
            'example.com'
        );

        $this->addToAssertionCount(1);
    }

    public function test_throws_when_no_status_reports(): void
    {
        $this->repository->method('findStatusReportsByAAGUID')->willReturn([]);

        $this->expectException(AuthenticatorResponseVerificationException::class);

        $this->step->process(
            $this->credentialSource,
            $this->buildAttestationResponse($this->makeAttestedCredentialData()),
            $this->options,
            null,
            'example.com'
        );
    }

    public function test_rejects_ctap1_u2f_authenticators_with_null_aaguid(): void
    {
        // CTAP1/U2F authenticators advertise the null UUID (00000000-...) as their AAGUID.
        // They have no FIDO MDS status reports, so they are always rejected here.
        // This is the primary safety net for authenticators absent from the MDS.
        $this->repository
            ->expects($this->once())
            ->method('findStatusReportsByAAGUID')
            ->with('00000000-0000-0000-0000-000000000000')
            ->willReturn([]);

        $this->expectException(AuthenticatorResponseVerificationException::class);

        $this->step->process(
            $this->credentialSource,
            $this->buildAttestationResponse($this->makeAttestedCredentialData()),
            $this->options,
            null,
            'example.com'
        );
    }

    /** @dataProvider fidoCertifiedStatusProvider */
    public function test_passes_for_all_fido_certified_levels(string $status): void
    {
        $this->repository->method('findStatusReportsByAAGUID')->willReturn([
            $this->makeStatusReport($status),
        ]);

        $this->step->process(
            $this->credentialSource,
            $this->buildAttestationResponse($this->makeAttestedCredentialData()),
            $this->options,
            null,
            'example.com'
        );

        $this->addToAssertionCount(1);
    }

    /** @return array<string, array{string}> */
    public static function fidoCertifiedStatusProvider(): array
    {
        return [
            'FIDO_CERTIFIED'        => [AuthenticatorStatus::FIDO_CERTIFIED],
            'FIDO_CERTIFIED_L1'     => [AuthenticatorStatus::FIDO_CERTIFIED_L1],
            'FIDO_CERTIFIED_L1plus' => [AuthenticatorStatus::FIDO_CERTIFIED_L1plus],
            'FIDO_CERTIFIED_L2'     => [AuthenticatorStatus::FIDO_CERTIFIED_L2],
            'FIDO_CERTIFIED_L2plus' => [AuthenticatorStatus::FIDO_CERTIFIED_L2plus],
            'FIDO_CERTIFIED_L3'     => [AuthenticatorStatus::FIDO_CERTIFIED_L3],
            'FIDO_CERTIFIED_L3plus' => [AuthenticatorStatus::FIDO_CERTIFIED_L3plus],
            'FIDO_CERTIFIED_L4'     => [AuthenticatorStatus::FIDO_CERTIFIED_L4],
            'FIDO_CERTIFIED_L5'     => [AuthenticatorStatus::FIDO_CERTIFIED_L5],
        ];
    }

    public function test_throws_when_not_fido_certified(): void
    {
        $this->repository->method('findStatusReportsByAAGUID')->willReturn([
            $this->makeStatusReport(AuthenticatorStatus::NOT_FIDO_CERTIFIED),
        ]);

        $this->expectException(AuthenticatorResponseVerificationException::class);

        $this->step->process(
            $this->credentialSource,
            $this->buildAttestationResponse($this->makeAttestedCredentialData()),
            $this->options,
            null,
            'example.com'
        );
    }

    public function test_uses_most_recent_report_by_effective_date(): void
    {
        // Newest-first order from the repository — sorting must pick the right one
        $this->repository->method('findStatusReportsByAAGUID')->willReturn([
            $this->makeStatusReport(AuthenticatorStatus::NOT_FIDO_CERTIFIED, '2024-01-01'),
            $this->makeStatusReport(AuthenticatorStatus::FIDO_CERTIFIED, '2023-01-01'),
        ]);

        $this->expectException(AuthenticatorResponseVerificationException::class);

        $this->step->process(
            $this->credentialSource,
            $this->buildAttestationResponse($this->makeAttestedCredentialData()),
            $this->options,
            null,
            'example.com'
        );
    }

    public function test_throws_for_revoked_status(): void
    {
        $this->repository->method('findStatusReportsByAAGUID')->willReturn([
            $this->makeStatusReport(AuthenticatorStatus::REVOKED),
        ]);

        $this->expectException(AuthenticatorResponseVerificationException::class);

        $this->step->process(
            $this->credentialSource,
            $this->buildAttestationResponse($this->makeAttestedCredentialData()),
            $this->options,
            null,
            'example.com'
        );
    }

    public function test_null_effective_date_is_treated_as_older_than_dated_report(): void
    {
        // Null-dated report (initial/undated) must sort before a dated newer report
        $this->repository->method('findStatusReportsByAAGUID')->willReturn([
            $this->makeStatusReport(AuthenticatorStatus::FIDO_CERTIFIED, '2024-01-01'),
            $this->makeStatusReport(AuthenticatorStatus::NOT_FIDO_CERTIFIED, null),
        ]);

        $this->step->process(
            $this->credentialSource,
            $this->buildAttestationResponse($this->makeAttestedCredentialData()),
            $this->options,
            null,
            'example.com'
        );

        $this->addToAssertionCount(1);
    }

    private function makeStatusReport(string $status, ?string $effectiveDate = null): StatusReport
    {
        return StatusReport::create($status, $effectiveDate, null, null, null, null, null, null);
    }
}
