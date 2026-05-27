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

namespace Test\Unit\Security;

use PHPUnit\Framework\TestCase;
use Surfnet\Webauthn\Exception\BackupEligibleRejectedException;
use Surfnet\Webauthn\Security\FailureHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class FailureHandlerTest extends TestCase
{
    private FailureHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new FailureHandler();
    }

    public function testSetsErrorCodeWhenBackupEligibleRejectionIsDirectCause(): void
    {
        $exception = new AuthenticationException('wrapper', 0, new BackupEligibleRejectedException('backup-eligible'));

        $response = $this->handler->onAuthenticationFailure(Request::create('/'), $exception);
        $body = json_decode((string) $response->getContent(), true);

        $this->assertSame('backup_eligible_rejected', $body['errorCode']);
        $this->assertSame('error', $body['status']);
    }

    public function testSetsErrorCodeWhenBackupEligibleRejectionIsNestedCause(): void
    {
        $inner = new BackupEligibleRejectedException('backup-eligible');
        $middle = new AuthenticationException('middle', 0, $inner);
        $outer = new AuthenticationException('outer', 0, $middle);

        $response = $this->handler->onAuthenticationFailure(Request::create('/'), $outer);
        $body = json_decode((string) $response->getContent(), true);

        $this->assertSame('backup_eligible_rejected', $body['errorCode']);
    }

    public function testNoErrorCodeForGenericFailure(): void
    {
        $exception = new AuthenticationException('generic failure');

        $response = $this->handler->onAuthenticationFailure(Request::create('/'), $exception);
        $body = json_decode((string) $response->getContent(), true);

        $this->assertArrayNotHasKey('errorCode', $body);
        $this->assertSame('error', $body['status']);
    }

    public function testReturns401(): void
    {
        $response = $this->handler->onAuthenticationFailure(Request::create('/'), new AuthenticationException());

        $this->assertSame(401, $response->getStatusCode());
    }
}
