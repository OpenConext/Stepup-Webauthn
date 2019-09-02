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

namespace App\Service;

use App\Entity\PublicKeyCredentialSource;
use App\Exception\AttestationCertificateNotSupportedException;
use App\Exception\AttestationStatementNotFoundException;
use App\Exception\InvalidTrustPathException;
use Webauthn\AttestationStatement\AttestationStatement;
use Webauthn\TrustPath\CertificateTrustPath;

class InMemoryAttestationCertificateTrustStore implements AttestationCertificateTrustStore
{
    private $trustedCertificates;

    public function __construct(array $trustedCertificates)
    {
        $this->trustedCertificates = $trustedCertificates;
    }

    public function validate(PublicKeyCredentialSource $source): void
    {
        if ($source->getAttestationType() === AttestationStatement::TYPE_NONE) {
            throw new AttestationStatementNotFoundException();
        }
        $trustPath = $source->getTrustPath();
        if (!$trustPath instanceof CertificateTrustPath) {
            throw new InvalidTrustPathException();
        }
        $certificates = $trustPath->getCertificates();
        if (!in_array(implode(PHP_EOL, $certificates), $this->trustedCertificates)) {
            throw new AttestationCertificateNotSupportedException();
        }
    }
}
