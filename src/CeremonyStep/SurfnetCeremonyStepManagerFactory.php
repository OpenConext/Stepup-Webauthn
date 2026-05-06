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

namespace Surfnet\Webauthn\CeremonyStep;

use LogicException;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\RSA\RS256;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\CeremonyStep\CeremonyStepManager;
use Webauthn\CeremonyStep\CheckAlgorithm;
use Webauthn\CeremonyStep\CheckAllowedCredentialList;
use Webauthn\CeremonyStep\CheckAllowedOrigins;
use Webauthn\CeremonyStep\CheckAttestationFormatIsKnownAndValid;
use Webauthn\CeremonyStep\CheckBackupBitsAreConsistent;
use Webauthn\CeremonyStep\CheckChallenge;
use Webauthn\CeremonyStep\CheckClientDataCollectorType;
use Webauthn\CeremonyStep\CheckCounter;
use Webauthn\CeremonyStep\CheckCredentialId;
use Webauthn\CeremonyStep\CheckExtensions;
use Webauthn\CeremonyStep\CheckHasAttestedCredentialData;
use Webauthn\CeremonyStep\CheckMetadataStatement;
use Webauthn\CeremonyStep\CheckOrigin;
use Webauthn\CeremonyStep\CheckRelyingPartyIdIdHash;
use Webauthn\CeremonyStep\CheckSignature;
use Webauthn\CeremonyStep\CheckTopOrigin;
use Webauthn\CeremonyStep\CheckUserHandle;
use Webauthn\CeremonyStep\CheckUserVerification;
use Webauthn\CeremonyStep\CheckUserWasPresent;
use Webauthn\CeremonyStep\TopOriginValidator;
use Webauthn\Counter\CounterChecker;
use Webauthn\Counter\ThrowExceptionIfInvalid;
use Webauthn\MetadataService\CertificateChain\CertificateChainValidator;
use Webauthn\MetadataService\MetadataStatementRepository;
use Webauthn\MetadataService\StatusReportRepository;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
final class SurfnetCeremonyStepManagerFactory
{
    private CounterChecker $counterChecker;

    private Manager $algorithmManager;

    private null|MetadataStatementRepository $metadataStatementRepository = null;

    private null|StatusReportRepository $statusReportRepository = null;

    private null|CertificateChainValidator $certificateChainValidator = null;

    private null|TopOriginValidator $topOriginValidator = null;

    /** @var string[]|null */
    private null|array $securedRelyingPartyId = null;

    /** @var string[]|null */
    private null|array $allowedOrigins = null;

    private bool $allowSubdomains = false;

    private AttestationStatementSupportManager $attestationStatementSupportManager;

    private ExtensionOutputCheckerHandler $extensionOutputCheckerHandler;

    public function __construct()
    {
        $this->counterChecker = new ThrowExceptionIfInvalid();
        $this->algorithmManager = Manager::create()->add(ES256::create(), RS256::create());
        $this->attestationStatementSupportManager = new AttestationStatementSupportManager([
            new NoneAttestationStatementSupport(),
        ]);
        $this->extensionOutputCheckerHandler = new ExtensionOutputCheckerHandler();
    }

    public function setCounterChecker(CounterChecker $counterChecker): void
    {
        $this->counterChecker = $counterChecker;
    }

    /**
     * @deprecated since 5.2.0 and will be removed in 6.0.0. Use setAllowedOrigins instead.
     * @todo Remove this method when upgrading webauthn-lib to 6.0.
     * @param string[] $securedRelyingPartyId
     */
    public function setSecuredRelyingPartyId(array $securedRelyingPartyId): void
    {
        $this->securedRelyingPartyId = $securedRelyingPartyId;
    }

    /**
     * @param string[] $allowedOrigins
     */
    public function setAllowedOrigins(array $allowedOrigins, bool $allowSubdomains = false): void
    {
        $this->allowedOrigins = $allowedOrigins;
        $this->allowSubdomains = $allowSubdomains;
    }

    public function setExtensionOutputCheckerHandler(ExtensionOutputCheckerHandler $extensionOutputCheckerHandler): void
    {
        $this->extensionOutputCheckerHandler = $extensionOutputCheckerHandler;
    }

    public function setAttestationStatementSupportManager(
        AttestationStatementSupportManager $attestationStatementSupportManager
    ): void {
        $this->attestationStatementSupportManager = $attestationStatementSupportManager;
    }

    public function setAlgorithmManager(Manager $algorithmManager): void
    {
        $this->algorithmManager = $algorithmManager;
    }

    public function enableMetadataStatementSupport(
        MetadataStatementRepository $metadataStatementRepository,
        StatusReportRepository $statusReportRepository,
        CertificateChainValidator $certificateChainValidator
    ): void {
        $this->metadataStatementRepository = $metadataStatementRepository;
        $this->statusReportRepository = $statusReportRepository;
        $this->certificateChainValidator = $certificateChainValidator;
    }

    public function enableCertificateChainValidator(CertificateChainValidator $certificateChainValidator): void
    {
        $this->certificateChainValidator = $certificateChainValidator;
    }

    public function enableTopOriginValidator(TopOriginValidator $topOriginValidator): void
    {
        $this->topOriginValidator = $topOriginValidator;
    }

    // Upgrade note: when bumping webauthn-lib, diff vendor CeremonyStepManagerFactory::creationCeremony()
    // and requestCeremony() against these lists and port any new or reordered steps.
    public function creationCeremony(): CeremonyStepManager
    {
        if ($this->metadataStatementRepository === null || $this->statusReportRepository === null) {
            throw new LogicException(
                'MDS must be configured before calling creationCeremony(). ' .
                'Call enableMetadataStatementSupport() first.'
            );
        }

        $metadataStatementChecker = new CheckMetadataStatement();
        if ($this->certificateChainValidator !== null) {
            $metadataStatementChecker->enableCertificateChainValidator($this->certificateChainValidator);
            $metadataStatementChecker->enableMetadataStatementSupport(
                $this->metadataStatementRepository,
                $this->statusReportRepository,
                $this->certificateChainValidator,
            );
        }

        $originStep = $this->allowedOrigins === null
            ? new CheckOrigin($this->securedRelyingPartyId ?? [])
            : new CheckAllowedOrigins($this->allowedOrigins, $this->allowSubdomains);

        return new CeremonyStepManager([
            new CheckClientDataCollectorType(),
            new CheckChallenge(),
            $originStep,
            new CheckTopOrigin($this->topOriginValidator),
            new CheckRelyingPartyIdIdHash(),
            new CheckUserWasPresent(),
            new CheckUserVerification(),
            new CheckNoBackupEligibility(),
            new CheckAlgorithm(),
            new CheckExtensions($this->extensionOutputCheckerHandler),
            new CheckAttestationIsNotNone(),
            new CheckAttestationFormatIsKnownAndValid($this->attestationStatementSupportManager),
            new CheckHasAttestedCredentialData(),
            $metadataStatementChecker,
            new CheckCredentialId(),
            new CheckHardwareKeyProtection($this->metadataStatementRepository),
            new CheckFidoCertified($this->statusReportRepository),
        ]);
    }

    public function requestCeremony(): CeremonyStepManager
    {
        $originStep = $this->allowedOrigins === null
            ? new CheckOrigin($this->securedRelyingPartyId ?? [])
            : new CheckAllowedOrigins($this->allowedOrigins, $this->allowSubdomains);

        return new CeremonyStepManager([
            new CheckAllowedCredentialList(),
            new CheckUserHandle(),
            new CheckClientDataCollectorType(),
            new CheckChallenge(),
            $originStep,
            new CheckTopOrigin(),
            new CheckRelyingPartyIdIdHash(),
            new CheckUserWasPresent(),
            new CheckUserVerification(),
            new CheckBackupBitsAreConsistent(),
            new CheckExtensions($this->extensionOutputCheckerHandler),
            new CheckSignature($this->algorithmManager),
            new CheckCounter($this->counterChecker),
        ]);
    }
}
