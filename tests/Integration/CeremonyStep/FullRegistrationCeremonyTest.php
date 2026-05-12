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

namespace Test\Integration\CeremonyStep;

use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithms;
use PHPUnit\Framework\TestCase;
use Surfnet\Webauthn\CeremonyStep\SurfnetCeremonyStepManagerFactory;
use Symfony\Component\Uid\Uuid;
use Webauthn\AttestedCredentialData;
use Webauthn\AttestationStatement\AttestationObject;
use Webauthn\AttestationStatement\AttestationStatement;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\PackedAttestationStatementSupport;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorData;
use Webauthn\CollectedClientData;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\MetadataService\CertificateChain\CertificateChainValidator;
use Webauthn\MetadataService\MetadataStatementRepository;
use Webauthn\MetadataService\Statement\AuthenticatorStatus;
use Webauthn\MetadataService\Statement\MetadataStatement;
use Webauthn\MetadataService\Statement\StatusReport;
use Webauthn\MetadataService\StatusReportRepository;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TrustPath\EmptyTrustPath;

/**
 * Full end-to-end registration ceremony test using real EC P-256 crypto.
 *
 * These tests replace the reflection-based step-list check in the unit tests:
 * if the factory wires steps in the wrong order, or a library upgrade silently
 * changes ceremony behaviour, these tests will fail with real crypto evidence.
 */
class FullRegistrationCeremonyTest extends TestCase
{
    private const RP_ID = 'example.com';
    private const ORIGIN = 'https://example.com';
    private const TEST_AAGUID = '550e8400-e29b-41d4-a716-446655440000';

    private const TEST_PRIVATE_KEY_PEM = <<<PEM
    -----BEGIN PRIVATE KEY-----
    MIGHAgEAMBMGByqGSM49AgEGCCqGSM49AwEHBG0wawIBAQQgbKkXB055IwqO6IlK
    IROwqe+eQY6ljMNghk/Oe7tOkDChRANCAATcLAujig+aLAX3qCZu52B9yEYTnHxG
    YqXiBjNzHVOUMqQpbhYW7DYoZzRH3ByNp/KWay02kv+V6cS/YcKBfxIn
    -----END PRIVATE KEY-----
    PEM;

    // Pre-computed COSE public key for TEST_PRIVATE_KEY_PEM (P-256, ES256)
    private const TEST_COSE_PUBLIC_KEY = 'pQECAyYgASFYINwsC6OKD5osBfeoJm7nYH3IRhOcfEZipeIGM3MdU5QyIlggpCluFhbsNihnNEfcHI2n8pZrLTaS/5XpxL9hwoF/Eic=';

    private const FLAGS_NORMAL = AuthenticatorData::FLAG_UP | AuthenticatorData::FLAG_UV | AuthenticatorData::FLAG_AT;
    private const FLAGS_BACKUP_ELIGIBLE = self::FLAGS_NORMAL | AuthenticatorData::FLAG_BE;

    public function test_valid_hardware_token_registration_passes(): void
    {
        [$response, $options] = $this->buildPackedRegistration(self::FLAGS_NORMAL);

        $validator = $this->buildValidator(
            mdsRepo: $this->hardwareMdsRepo(self::TEST_AAGUID),
            statusRepo: $this->fidoCertifiedStatusRepo(self::TEST_AAGUID),
        );

        $result = $validator->check($response, $options, self::RP_ID);

        $this->assertInstanceOf(PublicKeyCredentialSource::class, $result);
    }

    public function test_backup_eligible_credential_is_rejected(): void
    {
        [$response, $options] = $this->buildPackedRegistration(self::FLAGS_BACKUP_ELIGIBLE);

        $validator = $this->buildValidator(
            mdsRepo: $this->hardwareMdsRepo(self::TEST_AAGUID),
            statusRepo: $this->fidoCertifiedStatusRepo(self::TEST_AAGUID),
        );

        $this->expectException(AuthenticatorResponseVerificationException::class);
        $validator->check($response, $options, self::RP_ID);
    }

    public function test_none_attestation_is_rejected(): void
    {
        [$response, $options] = $this->buildNoneAttestationRegistration();

        $validator = $this->buildValidator(
            mdsRepo: $this->hardwareMdsRepo(self::TEST_AAGUID),
            statusRepo: $this->fidoCertifiedStatusRepo(self::TEST_AAGUID),
        );

        $this->expectException(AuthenticatorResponseVerificationException::class);
        $validator->check($response, $options, self::RP_ID);
    }

    public function test_no_fido_status_reports_is_rejected(): void
    {
        [$response, $options] = $this->buildPackedRegistration(self::FLAGS_NORMAL);

        $aaguid = self::TEST_AAGUID;
        $emptyStatusRepo = new class ($aaguid) implements StatusReportRepository {
            public function __construct(private readonly string $aaguid)
            {
            }

            /** @return StatusReport[] */
            public function findStatusReportsByAAGUID(string $aaguid): array
            {
                return [];
            }
        };

        $validator = $this->buildValidator(
            mdsRepo: $this->hardwareMdsRepo(self::TEST_AAGUID),
            statusRepo: $emptyStatusRepo,
        );

        $this->expectException(AuthenticatorResponseVerificationException::class);
        $validator->check($response, $options, self::RP_ID);
    }

    public function test_not_fido_certified_is_rejected(): void
    {
        [$response, $options] = $this->buildPackedRegistration(self::FLAGS_NORMAL);

        $aaguid = self::TEST_AAGUID;
        $revokedStatusRepo = new class ($aaguid) implements StatusReportRepository {
            public function __construct(private readonly string $aaguid)
            {
            }

            /** @return StatusReport[] */
            public function findStatusReportsByAAGUID(string $aaguid): array
            {
                if ($aaguid !== $this->aaguid) {
                    return [];
                }
                return [StatusReport::create(AuthenticatorStatus::NOT_FIDO_CERTIFIED, null, null, null, null, null, null, null)];
            }
        };

        $validator = $this->buildValidator(
            mdsRepo: $this->hardwareMdsRepo(self::TEST_AAGUID),
            statusRepo: $revokedStatusRepo,
        );

        $this->expectException(AuthenticatorResponseVerificationException::class);
        $validator->check($response, $options, self::RP_ID);
    }

    public function test_software_key_is_rejected(): void
    {
        [$response, $options] = $this->buildPackedRegistration(self::FLAGS_NORMAL);

        $aaguid = self::TEST_AAGUID;
        $softwareMdsRepo = new class ($aaguid) implements MetadataStatementRepository {
            public function __construct(private readonly string $aaguid)
            {
            }

            public function findOneByAAGUID(string $aaguid): ?MetadataStatement
            {
                if ($aaguid !== $this->aaguid) {
                    return null;
                }
                return MetadataStatement::create(
                    'Software Authenticator',
                    1,
                    'fido2',
                    3,
                    [],
                    [],
                    [],
                    ['basic_full'],
                    [],
                    [],
                    [],
                    [],
                    keyProtection: [MetadataStatement::KEY_PROTECTION_SOFTWARE],
                );
            }
        };

        $validator = $this->buildValidator(
            mdsRepo: $softwareMdsRepo,
            statusRepo: $this->fidoCertifiedStatusRepo(self::TEST_AAGUID),
        );

        $this->expectException(AuthenticatorResponseVerificationException::class);
        $validator->check($response, $options, self::RP_ID);
    }

    // --- Fixture builders ---

    /**
     * @return array{AuthenticatorAttestationResponse, PublicKeyCredentialCreationOptions}
     */
    private function buildPackedRegistration(int $flags): array
    {
        [$privateKey, $cosePublicKeyBytes] = $this->getTestKeypair();
        $credentialId = random_bytes(32);
        $challenge = random_bytes(32);

        $authDataBytes = $this->buildAuthData(self::TEST_AAGUID, $flags, $credentialId, $cosePublicKeyBytes);
        $clientDataJSON = $this->buildClientDataJson($challenge);
        $clientDataJSONHash = hash('sha256', $clientDataJSON, true);

        $dataToSign = $authDataBytes . $clientDataJSONHash;
        openssl_sign($dataToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        $attStmt = AttestationStatement::createSelf('packed', ['alg' => -7, 'sig' => $signature], new EmptyTrustPath());
        $response = $this->buildResponse($authDataBytes, $flags, $credentialId, $cosePublicKeyBytes, $attStmt, $clientDataJSON);
        $options = $this->buildCreationOptions($challenge);

        return [$response, $options];
    }

    /**
     * @return array{AuthenticatorAttestationResponse, PublicKeyCredentialCreationOptions}
     */
    private function buildNoneAttestationRegistration(): array
    {
        [, $cosePublicKeyBytes] = $this->getTestKeypair();
        $credentialId = random_bytes(32);
        $challenge = random_bytes(32);

        $authDataBytes = $this->buildAuthData(self::TEST_AAGUID, self::FLAGS_NORMAL, $credentialId, $cosePublicKeyBytes);
        $clientDataJSON = $this->buildClientDataJson($challenge);

        $attStmt = AttestationStatement::createNone('none', [], new EmptyTrustPath());
        $response = $this->buildResponse($authDataBytes, self::FLAGS_NORMAL, $credentialId, $cosePublicKeyBytes, $attStmt, $clientDataJSON);
        $options = $this->buildCreationOptions($challenge);

        return [$response, $options];
    }

    /**
     * @return array{\OpenSSLAsymmetricKey, string}
     */
    private function getTestKeypair(): array
    {
        return [
            openssl_pkey_get_private(self::TEST_PRIVATE_KEY_PEM),
            base64_decode(self::TEST_COSE_PUBLIC_KEY),
        ];
    }

    private function buildAuthData(string $aaguid, int $flags, string $credentialId, string $coseKeyBytes): string
    {
        $rpIdHash = hash('sha256', self::RP_ID, true);
        $flagsByte = chr($flags);
        $signCount = pack('N', 0);
        $aaguidBytes = hex2bin(str_replace('-', '', $aaguid));
        $credentialIdLength = pack('n', strlen($credentialId));

        return $rpIdHash . $flagsByte . $signCount . $aaguidBytes . $credentialIdLength . $credentialId . $coseKeyBytes;
    }

    private function buildClientDataJson(string $challenge): string
    {
        $challengeBase64 = sodium_bin2base64($challenge, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
        return (string) json_encode([
            'type' => 'webauthn.create',
            'challenge' => $challengeBase64,
            'origin' => self::ORIGIN,
        ]);
    }

    private function buildResponse(
        string $authDataBytes,
        int $flags,
        string $credentialId,
        string $cosePublicKeyBytes,
        AttestationStatement $attStmt,
        string $clientDataJSON
    ): AuthenticatorAttestationResponse {
        $aaguid = Uuid::fromString(self::TEST_AAGUID);
        $attestedCredentialData = AttestedCredentialData::create($aaguid, $credentialId, $cosePublicKeyBytes);
        $authData = AuthenticatorData::create(
            $authDataBytes,
            hash('sha256', self::RP_ID, true),
            chr($flags),
            0,
            $attestedCredentialData,
        );
        $attestationObject = AttestationObject::create('', $attStmt, $authData);
        $collectedClientData = CollectedClientData::create($clientDataJSON, (array) json_decode($clientDataJSON, true));

        return AuthenticatorAttestationResponse::create($collectedClientData, $attestationObject);
    }

    private function buildCreationOptions(string $challenge): PublicKeyCredentialCreationOptions
    {
        $rp = PublicKeyCredentialRpEntity::create(self::RP_ID, self::RP_ID);
        $user = PublicKeyCredentialUserEntity::create('user', 'user-handle', 'User');

        return PublicKeyCredentialCreationOptions::create($rp, $user, $challenge, [
            PublicKeyCredentialParameters::createPk(Algorithms::COSE_ALGORITHM_ES256),
        ]);
    }

    // --- MDS helpers ---

    private function buildValidator(
        MetadataStatementRepository $mdsRepo,
        StatusReportRepository $statusRepo,
    ): AuthenticatorAttestationResponseValidator {
        $certChainValidator = new class implements CertificateChainValidator {
            public function check(array $untrustedCertificates, array $trustedCertificates): void
            {
            }
        };

        $factory = new SurfnetCeremonyStepManagerFactory($mdsRepo, $statusRepo, $certChainValidator);
        $factory->setAllowedOrigins([self::ORIGIN]);
        $factory->setAttestationStatementSupportManager(
            new AttestationStatementSupportManager([
                new PackedAttestationStatementSupport(
                    Manager::create()->add(ES256::create())
                ),
            ])
        );

        return new AuthenticatorAttestationResponseValidator($factory->creationCeremony());
    }

    private function hardwareMdsRepo(string $aaguid): MetadataStatementRepository
    {
        return new class ($aaguid) implements MetadataStatementRepository {
            public function __construct(private readonly string $aaguid)
            {
            }

            public function findOneByAAGUID(string $aaguid): ?MetadataStatement
            {
                if ($aaguid !== $this->aaguid) {
                    return null;
                }
                return MetadataStatement::create(
                    'Test Hardware Authenticator',
                    1,
                    'fido2',
                    3,
                    [],
                    [],
                    [],
                    ['basic_full'],
                    [],
                    [],
                    [],
                    [],
                    keyProtection: [MetadataStatement::KEY_PROTECTION_HARDWARE],
                );
            }
        };
    }

    private function fidoCertifiedStatusRepo(string $aaguid): StatusReportRepository
    {
        return new class ($aaguid) implements StatusReportRepository {
            public function __construct(private readonly string $aaguid)
            {
            }

            /** @return StatusReport[] */
            public function findStatusReportsByAAGUID(string $aaguid): array
            {
                if ($aaguid !== $this->aaguid) {
                    return [];
                }
                return [StatusReport::create(AuthenticatorStatus::FIDO_CERTIFIED, '2024-01-01', null, null, null, null, null, null)];
            }
        };
    }
}
