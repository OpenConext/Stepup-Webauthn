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

namespace Surfnet\Webauthn\Service;

use Surfnet\Webauthn\Exception\AuthenticatorStatusNotSupportedException;
use Webauthn\MetadataService\Statement\AuthenticatorStatus;
use Webauthn\MetadataService\Statement\StatusReport;
use function array_walk;

class AuthenticatorStatusValidator
{
    /**
     * @var string[]
     */
    private readonly array $allowedStatus;

    public function __construct()
    {
        $this->allowedStatus = [
            AuthenticatorStatus::FIDO_CERTIFIED,
            AuthenticatorStatus::FIDO_CERTIFIED_L1,
            AuthenticatorStatus::FIDO_CERTIFIED_L2,
            AuthenticatorStatus::FIDO_CERTIFIED_L3,
            AuthenticatorStatus::FIDO_CERTIFIED_L4,
            AuthenticatorStatus::FIDO_CERTIFIED_L5,
            AuthenticatorStatus::FIDO_CERTIFIED_L1plus,
            AuthenticatorStatus::FIDO_CERTIFIED_L2plus,
            AuthenticatorStatus::FIDO_CERTIFIED_L3plus,
        ];
    }

    /**
     * One of the status reports must meet one of the allowed statuses.
     *
     * @param array<StatusReport> $statusReports
     * @throws AuthenticatorStatusNotSupportedException
     */
    public function validate(array $statusReports): void
    {
        $meetsRequirement = false;
        $reportsProcessed = 0;
        $reportLog = [];
        foreach ($statusReports as $report) {
            if (in_array($report->status, $this->allowedStatus)) {
                $meetsRequirement = true;
            }
            $reportsProcessed++;
            $reportLog[] = $report->status;
        }

        if (!$meetsRequirement) {
            throw new AuthenticatorStatusNotSupportedException(
                sprintf(
                    'Of the %d StatusReports tested, none met one of the required FIDO Certified statuses. ' .
                    'Reports tested: "%s"',
                    $reportsProcessed,
                    implode(', ', $reportLog)
                )
            );
        }
    }
}
