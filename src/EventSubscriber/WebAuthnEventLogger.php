<?php

declare(strict_types=1);

namespace Surfnet\Webauthn\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\Bundle\Event\PublicKeyCredentialCreationOptionsCreatedEvent;

final readonly class WebAuthnEventLogger implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private SerializerInterface $serializer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PublicKeyCredentialCreationOptionsCreatedEvent::class => 'onPublicKeyCredentialCreationOptionsCreated',
        ];
    }

    public function onPublicKeyCredentialCreationOptionsCreated(
        PublicKeyCredentialCreationOptionsCreatedEvent $event
    ): void {
        $json = $this->serializer->serialize(
            $event->publicKeyCredentialCreationOptions,
            JsonEncoder::FORMAT,
        );

        $this->logger->info('publicKeyCredentialCreationOptions', [
            'options' => $json,
        ]);
    }
}
