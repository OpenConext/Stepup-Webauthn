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

use Surfnet\Webauthn\Entity\PublicKeyCredentialSource;
use Surfnet\Webauthn\Exception\AttestationCertificateNotSupportedException;
use Surfnet\Webauthn\Exception\AttestationStatementNotFoundException;
use Surfnet\Webauthn\Exception\InvalidTrustPathException;
use PHPUnit\Framework\TestCase;
use Webauthn\AttestationStatement\AttestationStatement;
use Webauthn\TrustPath\CertificateTrustPath;
use Webauthn\TrustPath\EmptyTrustPath;

class InMemoryAttestationCertificateTrustStoreTest extends TestCase
{
    public function test__should_have_an_attestation_statement(): void
    {
        $this->expectException(AttestationStatementNotFoundException::class);
        $trustStore = new InMemoryAttestationCertificateTrustStore([]);
        $source = \Mockery::mock(PublicKeyCredentialSource::class);
        $source->shouldReceive(['getAttestationType' => AttestationStatement::TYPE_NONE]);
        $trustStore->validate($source);
    }

    public function test__should_have_certificate_trust_path(): void
    {
        $this->expectException(InvalidTrustPathException::class);
        $trustStore = new InMemoryAttestationCertificateTrustStore([]);
        $source = \Mockery::mock(PublicKeyCredentialSource::class);
        $source->shouldReceive([
            'getAttestationType' => AttestationStatement::TYPE_SELF,
            'getTrustPath' => new EmptyTrustPath(),
        ]);
        $trustStore->validate($source);
    }

    /**
     * @dataProvider invalidCertificatesProvider
     * @param string[] $keyCertificates
     * @param string[] $trustedCertificates
     */
    public function test__should_know_the_certificate_is_not_supported(
        array $keyCertificates,
        array $trustedCertificates
    ): void {
        $this->expectException(AttestationCertificateNotSupportedException::class);
        $trustStore = new InMemoryAttestationCertificateTrustStore($trustedCertificates);
        $source = \Mockery::mock(PublicKeyCredentialSource::class);
        $source->shouldReceive([
            'getAttestationType' => AttestationStatement::TYPE_SELF,
            'getTrustPath' => new CertificateTrustPath($keyCertificates),
        ]);
        $trustStore->validate($source);
    }

    /**
     * @dataProvider validCertificatesProvider
     * @param string[] $keyCertificates
     * @param string[] $trustedCertificates
     */
    public function test__should_accept_the_valid_certificates(array $keyCertificates, array $trustedCertificates): void
    {
        $trustStore = new InMemoryAttestationCertificateTrustStore($trustedCertificates);
        $source = \Mockery::mock(PublicKeyCredentialSource::class);
        $source->shouldReceive([
            'getAttestationType' => AttestationStatement::TYPE_SELF,
            'getTrustPath' => new CertificateTrustPath($keyCertificates),
        ]);
        $trustStore->validate($source);
        $this->assertNull(null);
    }

    public function invalidCertificatesProvider(): array
    {
        return [
            [[], []],
            [['cer 1'], []],
            [['cer 1'], ['cer 2']],
            [['cer 1'], ['cer 2', 'cer 4', 'cer']],
            [['cer 1', 'cer 3'], ['cer 1']],
        ];
    }

    public function validCertificatesProvider(): array
    {
        return [
            [['cer 1'], ['cer 1']],
            [['cer 2'], ['cer 1', 'cer 2', 'cer 3']],
            [['cer 1', 'cer 3'], ['cer 1' . PHP_EOL . 'cer 3']],
        ];
    }

}
