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

use Symfony\Component\Finder\Finder;

final class AttestationCertificateTrustStoreFactory
{
    /**
     * @var string
     */
    private $trustedCertificatesDirectory;

    public function __construct(string $trustedCertificatesDirectory)
    {
        $this->trustedCertificatesDirectory = $trustedCertificatesDirectory;
    }

    public function __invoke(): AttestationCertificateTrustStore
    {
        $finder = new Finder();
        $finder->files()->in($this->trustedCertificatesDirectory);
        $certificates = [];
        foreach ($finder as $file) {
            $certificates[] = $file->getContents();
        }
        return new InMemoryAttestationCertificateTrustStore($certificates);
    }
}
