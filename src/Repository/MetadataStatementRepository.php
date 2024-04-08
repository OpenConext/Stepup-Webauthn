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

namespace Surfnet\Webauthn\Repository;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Webauthn\MetadataService\CertificateChain\CertificateChainValidator;
use Webauthn\MetadataService\CertificateChain\CertificateToolbox;
use Webauthn\MetadataService\Exception\MetadataStatementLoadingException;
use Webauthn\MetadataService\Exception\MissingMetadataStatementException;
use Webauthn\MetadataService\Service\MetadataBLOBPayloadEntry;
use Webauthn\MetadataService\Statement\MetadataStatement;
use function file_get_contents;
use function hash;

class MetadataStatementRepository
{
    private array $statements;
    private array $statusReports;

    /**
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function __construct(
        private readonly string $jwtMdsBlobFileName,
        private readonly string $jwtMdsRootCertFileName,
        private readonly string $mdsCacheDir,
        private readonly CertificateChainValidator $certificateChainValidator,
    ) {
        $payload = $this->warmCache();
        $data = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

        foreach ($data['entries'] as $datum) {
            $entry = MetadataBLOBPayloadEntry::createFromArray($datum);

            $mds = $entry->metadataStatement;
            if ($mds !== null && $entry->aaguid !== null) {
                $this->statements[$entry->aaguid] = $mds;
                $this->statusReports[$entry->aaguid] = $entry->statusReports;
            }
        }
    }

    public function has(string $aaguid): bool
    {
        return array_key_exists($aaguid, $this->statements);
    }

    public function get(string $aaguid): MetadataStatement
    {
        array_key_exists($aaguid, $this->statements) || throw MissingMetadataStatementException::create($aaguid);
        return $this->statements[$aaguid];
    }

    public function list(): iterable
    {
        yield from array_keys($this->statements);
    }

    public function getStatusReports(string $aaguid): iterable
    {
        return $this->statusReports[$aaguid] ?? [];
    }

    private function validateCertificates(string ...$untrustedCertificates): void
    {
        $untrustedCertificates = CertificateToolbox::fixPEMStructures($untrustedCertificates);
        $rootCertificate = CertificateToolbox::convertDERToPEM(file_get_contents($this->jwtMdsRootCertFileName));
        $this->certificateChainValidator->check($untrustedCertificates, [$rootCertificate]);
    }

    private function getJwsPayload(string $token, array &$rootCertificates): string
    {
        $jws = (new CompactSerializer())->unserialize($token);
        $jws->countSignatures() === 1 || throw MetadataStatementLoadingException::create(
            'Invalid response from the metadata service. Only one signature shall be present.'
        );
        $signature = $jws->getSignature(0);
        $payload = $jws->getPayload();
        $payload !== '' || throw MetadataStatementLoadingException::create(
            'Invalid response from the metadata service. The token payload is empty.'
        );
        $header = $signature->getProtectedHeader();
        array_key_exists('alg', $header) || throw MetadataStatementLoadingException::create(
            'The "alg" parameter is missing.'
        );
        array_key_exists('x5c', $header) || throw MetadataStatementLoadingException::create(
            'The "x5c" parameter is missing.'
        );
        is_array($header['x5c']) || throw MetadataStatementLoadingException::create(
            'The "x5c" parameter should be an array.'
        );
        $key = JWKFactory::createFromX5C($header['x5c']);
        $rootCertificates = $header['x5c'];

        $verifier = new JWSVerifier(new AlgorithmManager([new ES256(), new RS256()]));
        $isValid = $verifier->verifyWithKey($jws, $key, 0);
        $isValid || throw MetadataStatementLoadingException::create(
            'Invalid response from the metadata service. The token signature is invalid.'
        );
        $payload = $jws->getPayload();
        $payload !== null || throw MetadataStatementLoadingException::create(
            'Invalid response from the metadata service. The payload is missing.'
        );

        return $payload;
    }

    private function warmCache(): string
    {
        $contents = file_get_contents($this->jwtMdsBlobFileName);
        $contentsSignature = hash('sha256', $contents);
        if (!file_exists($this->mdsCacheDir . $contentsSignature)) {
            $certificates = [];
            $payload = $this->getJwsPayload($contents, $certificates);
            $this->validateCertificates(... $certificates);
            file_put_contents($this->mdsCacheDir . $contentsSignature, $payload);
        }
        if (!isset($payload)) {
            // The cache is warmed up, retrieve the payload from the filesystem
            $payload = file_get_contents($this->mdsCacheDir . $contentsSignature);
        }

        return $payload;
    }
}
