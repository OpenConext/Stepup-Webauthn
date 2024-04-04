<?php

/**
 * Copyright 2024 SURFnet B.V.
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
use Webauthn\MetadataService\Exception\MissingMetadataStatementException;
use Webauthn\MetadataService\MetadataStatementRepository;
use Webauthn\MetadataService\Statement\MetadataStatement;
use Webauthn\MetadataService\StatusReportRepository;
use Surfnet\Webauthn\Repository\MetadataStatementRepository as SurfMetadataStatementRepository;
use Webauthn\PublicKeyCredentialSource;

class MetadataStatementService implements MetadataStatementRepository, StatusReportRepository
{
    public function __construct(
        private readonly SurfMetadataStatementRepository $repository,
        private readonly AuthenticatorStatusValidator $statusValidator
    ) {
    }

    public function findOneByAAGUID(string $aaguid): ?MetadataStatement
    {
        if ($this->repository->has($aaguid)) {
            try {
                return $this->repository->get($aaguid);
            } catch (MissingMetadataStatementException) {
                return null;
            }
        }
        return null;
    }

    public function findStatusReportsByAAGUID(string $aaguid): array
    {
        return (array) $this->repository->getStatusReports($aaguid);
    }

    /**
     * @throws AuthenticatorStatusNotSupportedException
     */
    public function verifyMeetsRequiredAuthenticatorStatus(PublicKeyCredentialSource $pkco): void
    {
        $statusReports = $this->findStatusReportsByAAGUID((string) $pkco->aaguid);
        $this->statusValidator->validate($statusReports);
    }
}
