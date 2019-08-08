<?php

/**
 * Copyright 2017 SURFnet B.V.
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

namespace App\Controller;

use Surfnet\GsspBundle\Exception\UnrecoverableErrorException;
use Surfnet\GsspBundle\Service\ValueStore\SessionValueStore;
use Webauthn\PublicKeyCredentialCreationOptions;

class PublicKeyCredentialCreationOptionsStore
{
    const KEY = 'PUBLIC_KEY_CREDENTIAL_CREATION_OPTIONS';
    private $store;

    public function __construct(SessionValueStore $sessionValueStore)
    {
        $this->store = $sessionValueStore;
    }

    public function get(): PublicKeyCredentialCreationOptions
    {
        if (!$this->store->has(self::KEY) || $this->store->get(self::KEY) === null) {
            throw new UnrecoverableErrorException('Unable to find the public key credential creation options');
        }
        return $this->store->get(self::KEY);
    }

    public function set(PublicKeyCredentialCreationOptions $options): void
    {
        $this->store->set(self::KEY, $options);
    }

    public function clear()
    {
        $this->store->set(self::KEY, null);
    }

}
