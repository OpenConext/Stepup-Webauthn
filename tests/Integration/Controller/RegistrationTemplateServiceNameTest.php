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

namespace Test\Integration\Controller;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Twig\Environment;

/**
 * Exercises the real Twig service and the actual registration.html.twig
 * template with the context Surfnet\Webauthn\Controller\RegistrationController
 * produces, to verify the SP name resolved from the mdui:UIInfo SAML extension
 * is rendered on the registration page (issue #258).
 */
class RegistrationTemplateServiceNameTest extends KernelTestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $request = Request::create('https://webauthn.dev.openconext.local/registration');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $container->get(RequestStack::class)->push($request);
        $this->twig = $container->get('twig');
    }

    public function testServiceNameIsRenderedOnTheRegistrationPageWhenPresent(): void
    {
        $html = $this->renderRegistrationPage('My Test Service');

        $this->assertStringContainsString('My Test Service', $html);
    }

    public function testPageRendersWithoutErrorAndWithoutServiceNameWhenAbsent(): void
    {
        $html = $this->renderRegistrationPage(null);

        $this->assertStringNotContainsString('My Test Service', $html);
    }

    private function renderRegistrationPage(?string $serviceName): string
    {
        return $this->twig->render('default/registration.html.twig', [
            'name' => 'test-username',
            'displayName' => 'Test User',
            'serviceName' => $serviceName,
        ]);
    }
}
