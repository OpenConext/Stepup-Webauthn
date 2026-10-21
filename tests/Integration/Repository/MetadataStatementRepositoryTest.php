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

namespace Test\Integration\Repository;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
use PHPUnit\Framework\TestCase;
use Surfnet\Webauthn\Repository\MetadataStatementRepository;
use Symfony\Component\Filesystem\Filesystem;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\MetadataService\CertificateChain\CertificateChainValidator;

class MetadataStatementRepositoryTest extends TestCase
{
    private const AAGUID_WITH_DETAILS = '2fc0579f-8113-47ea-b116-bb5a8db9202a';
    private const AAGUID_WITHOUT_DETAILS = '531126d6-e717-415c-9320-3d9aa6981239';

    private string $workDir;

    protected function setUp(): void
    {
        $this->workDir = sys_get_temp_dir() . '/mds-repository-test-' . bin2hex(random_bytes(4));
        mkdir($this->workDir . '/cache', 0777, true);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->workDir);
    }

    public function testStatementWithoutUserVerificationDetailsDoesNotBreakTheBlob(): void
    {
        $repository = $this->createRepository([
            self::AAGUID_WITH_DETAILS => $this->statement([
                'userVerificationDetails' => [[['userVerificationMethod' => 'passcode_internal']]],
            ]),
            self::AAGUID_WITHOUT_DETAILS => $this->statement(),
        ]);

        $this->assertTrue($repository->has(self::AAGUID_WITH_DETAILS));
        $this->assertTrue($repository->has(self::AAGUID_WITHOUT_DETAILS));
        $this->assertSame([], $repository->get(self::AAGUID_WITHOUT_DETAILS)->userVerificationDetails);
        $this->assertCount(1, $repository->get(self::AAGUID_WITH_DETAILS)->userVerificationDetails);
    }

    /**
     * @param array<string, array<string, mixed>> $statementsByAaguid
     */
    private function createRepository(array $statementsByAaguid): MetadataStatementRepository
    {
        $privateKey = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        $csr = openssl_csr_new(['commonName' => 'mds-test'], $privateKey);
        $certificate = openssl_csr_sign($csr, null, $privateKey, 1);
        openssl_x509_export($certificate, $certificatePem);
        openssl_pkey_export($privateKey, $privateKeyPem);

        $entries = [];
        foreach ($statementsByAaguid as $aaguid => $statement) {
            $entries[] = [
                'aaguid' => $aaguid,
                'metadataStatement' => $statement,
                'statusReports' => [['status' => 'FIDO_CERTIFIED_L1']],
                'timeOfLastStatusChange' => '2026-01-01',
            ];
        }
        $payload = json_encode(['no' => 1, 'nextUpdate' => '2026-12-01', 'entries' => $entries], JSON_THROW_ON_ERROR);

        $x5c = [trim(preg_replace('/-----(BEGIN|END) CERTIFICATE-----|\s/', '', $certificatePem))];
        $jws = (new JWSBuilder(new AlgorithmManager([new RS256()])))
            ->create()
            ->withPayload($payload)
            ->addSignature(JWKFactory::createFromKey($privateKeyPem), ['alg' => 'RS256', 'x5c' => $x5c])
            ->build();

        $blobFile = $this->workDir . '/blob.jwt';
        $rootFile = $this->workDir . '/root.crt';
        file_put_contents($blobFile, (new CompactSerializer())->serialize($jws, 0));
        file_put_contents($rootFile, $certificatePem);

        $noopValidator = new class implements CertificateChainValidator {
            public function check(array $untrustedCertificates, array $trustedCertificates): void
            {
            }
        };

        return new MetadataStatementRepository(
            $blobFile,
            $rootFile,
            $this->workDir . '/cache/',
            $noopValidator,
            (new WebauthnSerializerFactory(AttestationStatementSupportManager::create()))->create(),
        );
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function statement(array $overrides = []): array
    {
        return $overrides + [
            'description' => 'Test authenticator',
            'authenticatorVersion' => 1,
            'protocolFamily' => 'fido2',
            'schema' => 3,
            'upv' => [['major' => 1, 'minor' => 0]],
            'authenticationAlgorithms' => ['secp256r1_ecdsa_sha256_raw'],
            'publicKeyAlgAndEncodings' => ['cose'],
            'attestationTypes' => ['basic_full'],
            'matcherProtection' => ['software'],
            'tcDisplay' => [],
            'attestationRootCertificates' => [],
        ];
    }
}
