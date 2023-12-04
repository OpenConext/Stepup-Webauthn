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

use Surfnet\Webauthn\Controller\AssertionResponseController;
use Surfnet\Webauthn\Exception\NoActiveAuthenrequestException;
use Surfnet\Webauthn\PublicKeyCredentialRequestOptionsStore;
use Surfnet\Webauthn\ValidationJsonResponse;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Surfnet\GsspBundle\Exception\UnrecoverableErrorException;
use Surfnet\GsspBundle\Service\AuthenticationService;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\Component\HttpFoundation\Request;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialRequestOptions;

class AssertionResponseControllerTest extends TestCase
{
    use WebAuthnTestTrait;

    private AssertionResponseController $controller;
    private $publicKeyCredentialLoader;
    private $assertionResponseValidator;
    private $authenticationService;
    private $store;
    private BufferingLogger $logger;
    private $psr7Request;
    private $request;

    public function test__construct(): void
    {
        $this->assertInstanceOf(AssertionResponseController::class, $this->controller);
    }

    public function test__there_is_no_pending_authentication_from_SP(): void
    {
        $this->authenticationService->shouldReceive(['authenticationRequired' => false]);
        $this->assertEquals(
            ValidationJsonResponse::noAuthenticationRequired(new NoActiveAuthenrequestException()),
            $this->controller->action($this->psr7Request, $this->request)
        );
        $this->assertLogs();
    }

    public function test__if_it_fails_for_invalid_assertion_Response(): void
    {
        $this->authenticationService->shouldReceive([
            'authenticationRequired' => true,
            'getNameId' => 'JohnDoe123',
        ]);
        $this->setAuthenticatorResponse(Mockery::mock(AuthenticatorAttestationResponse::class));
        $this->assertEquals(
            ValidationJsonResponse::invalidPublicKeyCredentialResponse(new UnrecoverableErrorException('Invalid response type')),
            $this->controller->action($this->psr7Request, $this->request)
        );
        $this->assertLogs();
    }

    public function test__if_there_is_no_pending_credential_assert_options(): void
    {
        $this->authenticationService->shouldReceive([
            'authenticationRequired' => true,
            'getNameId' => 'JaneDoe123',
        ]);
        $this->setAuthenticatorResponse(Mockery::mock(AuthenticatorAssertionResponse::class));
        $this->store->shouldReceive('get')->andThrow(UnrecoverableErrorException::class, 'Some Error');
        $this->assertEquals(
            ValidationJsonResponse::noPendingCredentialAssertOptions(new UnrecoverableErrorException('Some Error')),
            $this->controller->action($this->psr7Request, $this->request)
        );
        $this->assertLogs();
    }

    public function test__if_public_key_credential_is_invalid(): void
    {
        $this->authenticationService->shouldReceive([
            'authenticationRequired' => true,
            'getNameId' => 'JaneDoe123',
        ]);
        $response = Mockery::mock(AuthenticatorAssertionResponse::class);
        $publicKeyCredential = $this->setAuthenticatorResponse($response);
        $publicKeyCredential->shouldReceive('getRawId')->andReturn('Public key credential raw id 1234');
        $options = new PublicKeyCredentialRequestOptions('challenge');
        $this->store->shouldReceive('get')->andReturn($options);
        $this->assertionResponseValidator
            ->shouldReceive('check')
            ->with(
                'Public key credential raw id 1234',
                $response,
                $options,
                $this->psr7Request,
                'JaneDoe123'
            )->andThrow(\Exception::class, 'Invalid');

        $this->assertEquals(
            ValidationJsonResponse::invalid(new \Exception('Invalid')),
            $this->controller->action($this->psr7Request, $this->request)
        );
        $this->assertLogs();
    }

    public function test__if_public_key_credential_is_valid(): void
    {
        $this->authenticationService->shouldReceive([
            'authenticationRequired' => true,
            'getNameId' => 'JaneDoe123',
        ]);
        $response = Mockery::mock(AuthenticatorAssertionResponse::class);
        $publicKeyCredential = $this->setAuthenticatorResponse($response);
        $publicKeyCredential->shouldReceive('getRawId')->andReturn('Public key credential raw id 1234');
        $options = new PublicKeyCredentialRequestOptions('challenge');
        $this->store->shouldReceive('get')->andReturn($options);
        $this->authenticationService->shouldReceive('authenticate');
        $this->store->shouldReceive('clear');
        $this->assertionResponseValidator
            ->shouldReceive('check')
            ->with(
                'Public key credential raw id 1234',
                $response,
                $options,
                $this->psr7Request,
                'JaneDoe123'
            );

        $this->assertEquals(
            ValidationJsonResponse::valid(),
            $this->controller->action($this->psr7Request, $this->request)
        );
        $this->authenticationService->shouldHaveReceived('authenticate');
        $this->store->shouldHaveReceived('clear');
        $this->assertLogs();
    }

    protected function setUp(): void
    {
        $this->publicKeyCredentialLoader = Mockery::mock(PublicKeyCredentialLoader::class);
        $this->assertionResponseValidator = Mockery::mock(AuthenticatorAssertionResponseValidator::class);
        $this->authenticationService = Mockery::mock(AuthenticationService::class);
        $this->assertionResponseValidator = Mockery::mock(AuthenticatorAssertionResponseValidator::class);
        $this->store = Mockery::mock(PublicKeyCredentialRequestOptionsStore::class);
        $this->logger = new BufferingLogger();
        $this->controller = new AssertionResponseController(
            $this->publicKeyCredentialLoader,
            $this->assertionResponseValidator,
            $this->authenticationService,
            $this->store,
            $this->logger
        );
        $this->psr7Request = Mockery::mock(ServerRequestInterface::class);
        $this->request = Mockery::mock(Request::class);
    }
}
