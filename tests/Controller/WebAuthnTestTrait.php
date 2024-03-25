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

namespace Test\Controller;

use Mockery;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Webauthn\AuthenticatorResponse;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialLoader;
use Symfony\Component\HttpFoundation\Request;

trait WebAuthnTestTrait
{
    /**
     * Only used for matching logs.
     */
    use MatchesSnapshots;

    /**
     * @var MockInterface|PublicKeyCredentialLoader
     */
    private $publicKeyCredentialLoader;

    private BufferingLogger $logger;
    /**
     * @var MockInterface|ServerRequestInterface
     */
    private $psr7Request;
    /**
     * @var MockInterface|Request
     */
    private $request;

    private function assertLogs(): void
    {
        $this->assertMatchesSnapshot($this->logger->cleanLogs());
    }

    private function setAuthenticatorResponse(AuthenticatorResponse $response): MockInterface&PublicKeyCredential
    {
        $content = 'The http content with AuthenticatorAssertionResponse';
        $this->request->shouldReceive([
            'getContent' => $content,
        ]);
        $publicKeyCredential = Mockery::mock(PublicKeyCredential::class);
        $publicKeyCredential->shouldReceive([
            'getResponse' => $response,
        ]);
        $this->publicKeyCredentialLoader
            ->shouldReceive('load')
            ->with($content)
            ->andReturn($publicKeyCredential);
        return $publicKeyCredential;
    }
}
