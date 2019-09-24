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

namespace App;

use App\Service\ClientMetadataService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GlobalViewParameters
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string[]
     */
    private $locales;

    /**
     * @var string[]
     */
    private $supportUrl;
    private $supportEmail;
    /**
     * @var ClientMetadataService
     */
    private $clientMetadataService;
    /**
     * @var RequestStack
     */
    private $request;

    /**
     * @param TranslatorInterface $translator
     * @param array $locales
     * @param ClientMetadataService $clientMetadataService
     * @param RequestStack $request
     * @param array $supportUrl
     * @param string|null $supportEmail
     */
    public function __construct(
        TranslatorInterface $translator,
        array $locales,
        ClientMetadataService $clientMetadataService,
        RequestStack $request,
        array $supportUrl,
        string $supportEmail = null
    ) {
        $this->translator = $translator;
        $this->locales = $locales;
        $this->supportUrl = $supportUrl;
        $this->supportEmail = $supportEmail;
        $this->clientMetadataService = $clientMetadataService;
        $this->request = $request;
    }

    /**
     * @return string
     */
    public function getSupportUrl()
    {
        return $this->supportUrl[$this->translator->getLocale()];
    }

    /**
     * Matches RequestInformation type interface.
     *
     * @return array
     */
    public function getRequestInformation(): array
    {
        $metadata = $this->clientMetadataService->generateMetadata($this->request->getCurrentRequest());
        return [
            'supportEmail' => $this->supportEmail,
            'hostname' => $metadata['hostname'],
            'ipAddress' => $metadata['ip_address'],
            'requestId' => $metadata['request_id'],
            'sari' => $metadata['sari'],
            'userAgent' => $metadata['user_agent'],
        ];
    }
}
