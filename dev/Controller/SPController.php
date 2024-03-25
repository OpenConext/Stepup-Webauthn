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

namespace Dev\Controller;

use Exception;
use SAML2\Message;
use SAML2\Response as SamlResponse;
use SAML2\StatusResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use DOMDocument;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\Assertion;
use SAML2\Certificate\PrivateKeyLoader;
use SAML2\Configuration\PrivateKey;
use SAML2\DOMDocumentFactory;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\Http\Exception\AuthnFailedSamlResponseException;
use Surfnet\SamlBundle\Http\PostBinding;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\SamlBundle\SAML2\AuthnRequestFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Demo SP.
 */
final class SPController extends AbstractController
{
    public function __construct(
        private readonly ServiceProvider $serviceProvider,
        private readonly IdentityProvider $identityProvider,
        private readonly PostBinding $postBinding
    ) {
    }

    #[Route(path: '/demo/sp', name: 'sp_demo', methods: ['GET', 'POST'])]
    #[Route(path: '/', name: 'homepage', methods: ['GET', 'POST'])]
    public function demoSp(Request $request): Response
    {
        if (!$request->isMethod(Request::METHOD_POST)) {
            return $this->render('dev/sp.html.twig', ['nameId' => $request->get('nameId')]);
        }
        $authnRequest = AuthnRequestFactory::createNewRequest($this->serviceProvider, $this->identityProvider);

        // Set nameId when we want to authenticate.
        if ($request->get('action') === 'authenticate') {
            $authnRequest->setSubject($request->get('NameID'));
        }

        // Build request query parameters.
        $requestAsXml = $authnRequest->getUnsignedXML();
        $encodedRequest = base64_encode(gzdeflate($requestAsXml));
        $queryParams = [AuthnRequest::PARAMETER_REQUEST => $encodedRequest];
        $relayState = $request->get(AuthnRequest::PARAMETER_RELAY_STATE);
        if (!empty($relayState)) {
            $queryParams[AuthnRequest::PARAMETER_RELAY_STATE] = $relayState;
        }

        // Create redirect response.
        $query = $this->signRequestQuery($queryParams);
        $url = sprintf('%s?%s', $this->identityProvider->getSsoUrl(), $query);
        $response = new RedirectResponse($url);

        // Set Stepup request id header.
        $stepupRequestId = $request->get('X-Stepup-Request-Id');
        if (!empty($stepupRequestId)) {
            $response->headers->set('X-Stepup-Request-Id', $stepupRequestId);
        }

        return $response;
    }

    #[Route(path: '/demo/sp/acs', name: 'sp_demo_acs', methods: ['POST'])]
    public function assertionConsumerService(Request $request): Response
    {
        $xmlResponse = $request->request->get('SAMLResponse');
        $xml = base64_decode($xmlResponse);
        try {
            /** @var Assertion $response */
            $response = $this->postBinding->processResponse($request, $this->identityProvider, $this->serviceProvider);

            $nameID = $response->getNameId();

            return $this->render('dev/acs.html.twig', [
                'requestId' => $response->getId(),
                'nameId' => $nameID instanceof \SAML2\XML\saml\NameID ? [
                    'value' => $nameID->getValue(),
                    'format' => $nameID->getFormat(),
                ] : [],
                'issuer' => $response->getIssuer(),
                'relayState' => $request->get(AuthnRequest::PARAMETER_RELAY_STATE, ''),
                'authenticatingAuthority' => $response->getAuthenticatingAuthority(),
                'xml' => $this->toFormattedXml($xml),
            ]);
        } catch (AuthnFailedSamlResponseException $e) {
            $samlResponse = $this->toUnsignedErrorResponse($xml);

            return $this->render('dev/acs-error-response.html.twig', [
                'error' => $e->getMessage(),
                'status' => $samlResponse->getStatus(),
                'requestId' => $samlResponse->getId(),
                'issuer' => $samlResponse->getIssuer(),
                'relayState' => $request->get(AuthnRequest::PARAMETER_RELAY_STATE, ''),
                'xml' => $this->toFormattedXml($xml),
            ]);
        }
    }

    /**
     * Formats xml.
     */
    private function toFormattedXml(string $xml): string|false
    {
        $domXml = new DOMDocument('1.0');
        $domXml->preserveWhiteSpace = false;
        $domXml->formatOutput = true;
        $domXml->loadXML($xml);

        return $domXml->saveXML();
    }

    /**
     * Sign AuthnRequest query parameters.
     * @throws Exception
     */
    private function signRequestQuery(array $queryParams): string
    {
        $securityKey = $this->loadServiceProviderPrivateKey();
        $queryParams[AuthnRequest::PARAMETER_SIGNATURE_ALGORITHM] = $securityKey->type;
        $toSign = http_build_query($queryParams);
        $signature = $securityKey->signData($toSign);

        return $toSign.'&Signature='.urlencode(base64_encode((string) $signature));
    }

    /**
     * Loads the private key from the service provider.
     *
     * @throws Exception
     */
    private function loadServiceProviderPrivateKey(): XMLSecurityKey
    {
        $keyLoader = new PrivateKeyLoader();
        $privateKey = $keyLoader->loadPrivateKey(
            $this->serviceProvider->getPrivateKey(PrivateKey::NAME_DEFAULT)
        );
        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $key->loadKey($privateKey->getKeyAsString());

        return $key;
    }

    /**
     * @throws Exception
     */
    private function toUnsignedErrorResponse(string $xml): StatusResponse
    {
        $previous = libxml_disable_entity_loader(true);
        $asXml = DOMDocumentFactory::fromString($xml);
        libxml_disable_entity_loader($previous);

        /** @var StatusResponse $statusResponse */
        $statusResponse = SamlResponse::fromXML($asXml->documentElement);
        return $statusResponse;
    }
}
