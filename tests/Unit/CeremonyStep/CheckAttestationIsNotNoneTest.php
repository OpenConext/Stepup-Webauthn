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

use Psr\Log\NullLogger;
use Surfnet\Webauthn\CeremonyStep\CheckAttestationIsNotNone;
use Webauthn\AttestationStatement\AttestationStatement;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\Exception\AuthenticatorResponseVerificationException;

class CheckAttestationIsNotNoneTest extends AbstractCeremonyStepTestCase
{
    private CheckAttestationIsNotNone $step;

    protected function setUp(): void
    {
        parent::setUp();
        $this->step = new CheckAttestationIsNotNone(new NullLogger());
    }

    public function test_skips_assertion_response(): void
    {
        $this->step->process(
            $this->credentialSource,
            $this->createMock(AuthenticatorAssertionResponse::class),
            $this->options,
            null,
            'example.com'
        );

        $this->addToAssertionCount(1);
    }

    public function test_throws_for_type_none(): void
    {
        $this->expectException(AuthenticatorResponseVerificationException::class);

        $this->step->process(
            $this->credentialSource,
            $this->buildAttestationResponse(attStmtType: AttestationStatement::TYPE_NONE),
            $this->options,
            null,
            'example.com'
        );
    }

    public function test_passes_for_type_basic(): void
    {
        $this->step->process(
            $this->credentialSource,
            $this->buildAttestationResponse(attStmtType: AttestationStatement::TYPE_BASIC),
            $this->options,
            null,
            'example.com'
        );

        $this->addToAssertionCount(1);
    }
}
