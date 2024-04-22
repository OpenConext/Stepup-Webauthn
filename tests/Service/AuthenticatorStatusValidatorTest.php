<?php

/**
 * Copyright 2019 SURFnet B.V.
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

namespace Test\Service;

use PHPUnit\Framework\TestCase;
use Surfnet\Webauthn\Exception\AuthenticatorStatusNotSupportedException;
use Surfnet\Webauthn\Service\AuthenticatorStatusValidator;
use Webauthn\MetadataService\Statement\AuthenticatorStatus;
use Webauthn\MetadataService\Statement\StatusReport;

class AuthenticatorStatusValidatorTest extends TestCase
{
    private AuthenticatorStatusValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new AuthenticatorStatusValidator();
    }

    /**
     * @dataProvider validReports
     */
    public function test_allows_allowed_statusses(array $reports)
    {
        // The validator will not return a thing when things go well
        $this->expectNotToPerformAssertions();
        $this->validator->validate($reports);
    }

    /**
     * @dataProvider invalidReports
     */
    public function test_rejects_invalid_statusses(array $reports)
    {
        $this->expectException(AuthenticatorStatusNotSupportedException::class);
        $this->validator->validate($reports);
    }

    public function validReports()
    {
        return [
            'valid FIDO_CERTIFIED' => [[$this->createReport(AuthenticatorStatus::FIDO_CERTIFIED)]],
            'valid FIDO_CERTIFIED L1' => [[$this->createReport(AuthenticatorStatus::FIDO_CERTIFIED_L1)]],
            'valid FIDO_CERTIFIED L2' => [[$this->createReport(AuthenticatorStatus::FIDO_CERTIFIED_L2)]],
            'valid FIDO_CERTIFIED L3' => [[$this->createReport(AuthenticatorStatus::FIDO_CERTIFIED_L3)]],
            'valid FIDO_CERTIFIED L4' => [[$this->createReport(AuthenticatorStatus::FIDO_CERTIFIED_L4)]],
            'valid FIDO_CERTIFIED L5' => [[$this->createReport(AuthenticatorStatus::FIDO_CERTIFIED_L5)]],
            'valid FIDO_CERTIFIED L1+' => [[$this->createReport(AuthenticatorStatus::FIDO_CERTIFIED_L1plus)]],
            'valid FIDO_CERTIFIED L2+' => [[$this->createReport(AuthenticatorStatus::FIDO_CERTIFIED_L2plus)]],
            'valid FIDO_CERTIFIED L3+' => [[$this->createReport(AuthenticatorStatus::FIDO_CERTIFIED_L3plus)]],
            'valid FIDO_CERTIFIED mixed all good' => [[$this->createReport(AuthenticatorStatus::FIDO_CERTIFIED_L5), $this->createReport(AuthenticatorStatus::FIDO_CERTIFIED)]],
            'valid FIDO_CERTIFIED mixed good and bad' => [[$this->createReport(AuthenticatorStatus::NOT_FIDO_CERTIFIED), $this->createReport(AuthenticatorStatus::FIDO_CERTIFIED)]],
        ];
    }

    public function invalidReports()
    {
        return [
            'invalid NOT_FIDO_CERTIFIED' => [[$this->createReport(AuthenticatorStatus::NOT_FIDO_CERTIFIED)]],
            'invalid USER_VERIFICATION_BYPASS' => [[$this->createReport(AuthenticatorStatus::NOT_FIDO_CERTIFIED)]],
            'invalid ATTESTATION_KEY_COMPROMISE' => [[$this->createReport(AuthenticatorStatus::ATTESTATION_KEY_COMPROMISE)]],
            'invalid USER_KEY_REMOTE_COMPROMISE' => [[$this->createReport(AuthenticatorStatus::USER_KEY_REMOTE_COMPROMISE)]],
            'invalid USER_KEY_PHYSICAL_COMPROMISE' => [[$this->createReport(AuthenticatorStatus::USER_KEY_PHYSICAL_COMPROMISE)]],
            'invalid UPDATE_AVAILABLE' => [[$this->createReport(AuthenticatorStatus::UPDATE_AVAILABLE)]],
            'invalid REVOKED' => [[$this->createReport(AuthenticatorStatus::REVOKED)]],
            'invalid mixed, all bad' => [[$this->createReport(AuthenticatorStatus::UPDATE_AVAILABLE), $this->createReport(AuthenticatorStatus::NOT_FIDO_CERTIFIED)]],
            'invalid FIDO_CERTIFIED and REVOKED' => [[$this->createReport(AuthenticatorStatus::REVOKED), $this->createReport(AuthenticatorStatus::FIDO_CERTIFIED)]],
        ];
    }
    final public const USER_VERIFICATION_BYPASS = '';

    final public const ATTESTATION_KEY_COMPROMISE = '';

    final public const USER_KEY_REMOTE_COMPROMISE = '';

    final public const USER_KEY_PHYSICAL_COMPROMISE = '';

    final public const UPDATE_AVAILABLE = '';

    final public const REVOKED = '';

    final public const SELF_ASSERTION_SUBMITTED = 'SELF_ASSERTION_SUBMITTED';
    private function createReport(string $status): StatusReport
    {
        return new StatusReport($status, null, null, null, null, null, null, null);
    }
}
