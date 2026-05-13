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

use Surfnet\Webauthn\CeremonyStep\SurfnetCeremonyStepManagerFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Webauthn\CeremonyStep\CeremonyStepManager;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;

/**
 * Verifies that the Symfony container correctly wires SurfnetCeremonyStepManagerFactory.
 * Catches DI misconfiguration that manual construction in other tests cannot detect.
 */
class CeremonyStepManagerFactoryWiringTest extends KernelTestCase
{
    public function test_factory_resolves_to_surfnet_implementation(): void
    {
        self::bootKernel();

        $factory = static::getContainer()->get(CeremonyStepManagerFactory::class);

        $this->assertInstanceOf(SurfnetCeremonyStepManagerFactory::class, $factory);
    }

    public function test_creation_ceremony_builds_without_error(): void
    {
        self::bootKernel();

        /** @var SurfnetCeremonyStepManagerFactory $factory */
        $factory = static::getContainer()->get(CeremonyStepManagerFactory::class);

        $this->assertInstanceOf(CeremonyStepManager::class, $factory->creationCeremony());
    }

    public function test_request_ceremony_builds_without_error(): void
    {
        self::bootKernel();

        /** @var SurfnetCeremonyStepManagerFactory $factory */
        $factory = static::getContainer()->get(CeremonyStepManagerFactory::class);

        $this->assertInstanceOf(CeremonyStepManager::class, $factory->requestCeremony());
    }
}
