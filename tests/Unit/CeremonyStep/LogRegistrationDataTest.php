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

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Surfnet\Webauthn\CeremonyStep\LogRegistrationData;
use Webauthn\AuthenticatorAssertionResponse;

class LogRegistrationDataTest extends AbstractCeremonyStepTestCase
{
    private LogRegistrationData $step;

    protected function setUp(): void
    {
        parent::setUp();
        $this->step = new LogRegistrationData(new NullLogger());
    }

    public function testSkipsAssertionResponseWithoutLogging(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('info');


        $step = new LogRegistrationData($logger);
        $step->process(
            $this->credentialSource,
            $this->createMock(AuthenticatorAssertionResponse::class),
            $this->options,
            null,
            'example.com'
        );
    }

    public function testLogsInfoForAttestationResponse(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())->method('info');

        $step = new LogRegistrationData($logger);
        $step->process(
            $this->credentialSource,
            $this->buildAttestationResponse($this->makeAttestedCredentialData()),
            $this->options,
            null,
            'example.com'
        );
    }

    public function testLogsRawAttestationDataAtInfo(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))->method('info');

        $step = new LogRegistrationData($logger);
        $step->process(
            $this->credentialSource,
            $this->buildAttestationResponse($this->makeAttestedCredentialData()),
            $this->options,
            null,
            'example.com'
        );
    }

    public function testNeverThrows(): void
    {
        $this->step->process(
            $this->credentialSource,
            $this->buildAttestationResponse($this->makeAttestedCredentialData()),
            $this->options,
            null,
            'example.com'
        );

        $this->addToAssertionCount(1);
    }

    public function testNeverThrowsWithoutAttestedCredentialData(): void
    {
        $this->step->process(
            $this->credentialSource,
            $this->buildAttestationResponse(),
            $this->options,
            null,
            'example.com'
        );

        $this->addToAssertionCount(1);
    }
}
