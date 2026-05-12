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

namespace Test\Unit\CeremonyStep;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Surfnet\Webauthn\CeremonyStep\CheckAttestationIsNotNone;
use Surfnet\Webauthn\CeremonyStep\CheckFidoCertified;
use Surfnet\Webauthn\CeremonyStep\CheckHardwareKeyProtection;
use Surfnet\Webauthn\CeremonyStep\CheckNoBackupEligibility;
use Surfnet\Webauthn\CeremonyStep\SurfnetCeremonyStepManagerFactory;
use Webauthn\CeremonyStep\CeremonyStepManager;
use Webauthn\CeremonyStep\CheckAlgorithm;
use Webauthn\CeremonyStep\CheckAllowedCredentialList;
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
use Webauthn\MetadataService\CertificateChain\CertificateChainValidator;
use Webauthn\MetadataService\MetadataStatementRepository;
use Webauthn\MetadataService\StatusReportRepository;

class SurfnetCeremonyStepManagerFactoryTest extends TestCase
{
    private SurfnetCeremonyStepManagerFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new SurfnetCeremonyStepManagerFactory(
            $this->createMock(MetadataStatementRepository::class),
            $this->createMock(StatusReportRepository::class),
            $this->createMock(CertificateChainValidator::class),
        );
    }

    public function test_creation_ceremony_with_mds_has_exact_expected_steps(): void
    {
        $manager = $this->factory->creationCeremony();
        $stepClasses = $this->getStepClasses($manager);

        $this->assertSame([
            CheckClientDataCollectorType::class,
            CheckChallenge::class,
            CheckOrigin::class,
            CheckTopOrigin::class,
            CheckRelyingPartyIdIdHash::class,
            CheckUserWasPresent::class,
            CheckUserVerification::class,
            CheckNoBackupEligibility::class,
            CheckAlgorithm::class,
            CheckExtensions::class,
            CheckAttestationIsNotNone::class,
            CheckAttestationFormatIsKnownAndValid::class,
            CheckHasAttestedCredentialData::class,
            CheckMetadataStatement::class,
            CheckCredentialId::class,
            CheckHardwareKeyProtection::class,
            CheckFidoCertified::class,
        ], $stepClasses);
    }

    public function test_creation_ceremony_does_not_include_backup_bits_consistent(): void
    {
        $manager = $this->factory->creationCeremony();
        $stepClasses = $this->getStepClasses($manager);

        $this->assertNotContains(CheckBackupBitsAreConsistent::class, $stepClasses);
    }

    public function test_request_ceremony_has_expected_steps(): void
    {
        $manager = $this->factory->requestCeremony();
        $stepClasses = $this->getStepClasses($manager);

        $this->assertSame([
            CheckAllowedCredentialList::class,
            CheckUserHandle::class,
            CheckClientDataCollectorType::class,
            CheckChallenge::class,
            CheckOrigin::class,
            CheckTopOrigin::class,
            CheckRelyingPartyIdIdHash::class,
            CheckUserWasPresent::class,
            CheckUserVerification::class,
            CheckBackupBitsAreConsistent::class,
            CheckExtensions::class,
            CheckSignature::class,
            CheckCounter::class,
        ], $stepClasses);
    }

    public function test_request_ceremony_does_not_include_registration_steps(): void
    {
        $manager = $this->factory->requestCeremony();
        $stepClasses = $this->getStepClasses($manager);

        $this->assertNotContains(CheckAttestationIsNotNone::class, $stepClasses);
        $this->assertNotContains(CheckNoBackupEligibility::class, $stepClasses);
        $this->assertNotContains(CheckHardwareKeyProtection::class, $stepClasses);
        $this->assertNotContains(CheckFidoCertified::class, $stepClasses);
    }

    /** @return string[] */
    private function getStepClasses(CeremonyStepManager $manager): array
    {
        $reflection = new ReflectionClass($manager);
        $property = $reflection->getProperty('steps');
        $property->setAccessible(true);
        $steps = $property->getValue($manager);

        return array_map(fn($step) => get_class($step), $steps);
    }
}
