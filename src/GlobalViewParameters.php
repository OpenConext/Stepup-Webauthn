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
     * @param TranslatorInterface $translator
     * @param array $locales
     * @param array $supportUrl
     * @param string|null $supportEmail
     */
    public function __construct(TranslatorInterface $translator, array $locales, array $supportUrl, string $supportEmail = null)
    {
        $this->translator = $translator;
        $this->locales = $locales;
        $this->supportUrl = $supportUrl;
        $this->supportEmail = $supportEmail;
    }

    /**
     * @return string
     */
    public function getSupportUrl()
    {
        return $this->supportUrl[$this->translator->getLocale()];
    }

    /**
     * @return string
     */
    public function getSupportEmail()
    {
        return $this->supportEmail;
    }
}
