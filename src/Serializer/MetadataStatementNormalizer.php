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

namespace Surfnet\Webauthn\Serializer;

use ReflectionClass;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Webauthn\MetadataService\Statement\MetadataStatement;

class MetadataStatementNormalizer extends AbstractObjectNormalizer
{
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct()
    {
        parent::__construct(defaultContext: [
            self::DEFAULT_CONSTRUCTOR_ARGUMENTS => [
                MetadataStatement::class => [
                    'userVerificationDetails' => [],
                    'upv' => [],
                    'authenticationAlgorithms' => [],
                    'publicKeyAlgAndEncodings' => [],
                    'attestationTypes' => [],
                    'matcherProtection' => [],
                    'tcDisplay' => [],
                    'attestationRootCertificates' => [],
                ],
            ],
        ]);

        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            MetadataStatement::class => true,
        ];
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === MetadataStatement::class;
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function extractAttributes(object $object, ?string $format = null, array $context = []): array
    {
        $reflectionClass = new ReflectionClass($object);
        $attributes = [];

        foreach ($reflectionClass->getProperties() as $property) {
            if ($this->isAllowedAttribute($object, $property->getName(), $format, $context)) {
                $attributes[] = $property->getName();
            }
        }

        return $attributes;
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function getAttributeValue(object $object, string $attribute, ?string $format = null, array $context = []): mixed
    {
        return $this->propertyAccessor->getValue($object, $attribute);
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function setAttributeValue(object $object, string $attribute, mixed $value, ?string $format = null, array $context = []): void
    {
        $this->propertyAccessor->setValue($object, $attribute, $value);
    }
}
