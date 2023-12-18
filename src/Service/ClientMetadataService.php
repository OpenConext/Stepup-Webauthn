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

namespace Surfnet\Webauthn\Service;

use DateTime;
use Surfnet\GsspBundle\Service\StateHandlerInterface;
use Surfnet\StepupBundle\Request\RequestId;
use Symfony\Component\HttpFoundation\Request;

final readonly class ClientMetadataService
{
    public function __construct(
        private RequestId $requestId,
        private StateHandlerInterface $stateHandler
    ) {
    }

    public function generateMetadata(Request $request): array
    {
        $timestamp = (new DateTime)->format(DateTime::ISO8601);
        $hostname = $request->getHost();
        $userAgent = $request->headers->get('User-Agent');
        $ipAddress = $request->getClientIp();
        return [
            'timestamp' => $timestamp,
            'hostname' => $hostname,
            'request_id' => $this->requestId->get(),
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
            'sari' => $this->stateHandler->hasRequestId() ? $this->stateHandler->getRequestId() : null
        ];
    }
}
