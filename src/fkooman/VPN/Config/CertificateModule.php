<?php

/**
 * Copyright 2016 François Kooman <fkooman@tuxed.net>.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace fkooman\VPN\Config;

use fkooman\Rest\Plugin\Authentication\Bearer\TokenInfo;
use fkooman\Rest\Service;
use fkooman\Rest\ServiceModuleInterface;
use fkooman\Http\Request;
use fkooman\Http\Response;
use fkooman\Http\Exception\BadRequestException;
use fkooman\Http\Exception\ForbiddenException;
use fkooman\Http\Exception\NotFoundException;
use fkooman\Http\JsonResponse;
use Psr\Log\LoggerInterface;

class CertificateModule implements ServiceModuleInterface
{
    /** @var CaInterface */
    private $ca;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    public function __construct(CaInterface $ca, LoggerInterface $logger)
    {
        $this->ca = $ca;
        $this->logger = $logger;
    }

    public function init(Service $service)
    {
        $service->post(
            '/certificate/',
            function (Request $request, TokenInfo $tokenInfo) {
                $certType = InputValidation::certType(
                    $request->getPostParameter('cert_type')
                );
                $commonName = InputValidation::commonName(
                    $request->getPostParameter('common_name')
                );

                if ('client' === $certType) {
                    self::requireScope($tokenInfo, 'issue_client');
                    $this->logger->info('issuing client certificate', array('cn' => $commonName));

                    return $this->generateCert($commonName, false);
                } elseif ('server' === $certType) {
                    self::requireScope($tokenInfo, 'issue_server');
                    $this->logger->info('issuing server certificate', array('cn' => $commonName));

                    return $this->generateCert($commonName, true);
                }

                throw new BadRequestException('invalid "cert_type"');
            }
        );

        $service->delete(
            '/certificate/:commonName',
            function ($commonName, TokenInfo $tokenInfo) {
                self::requireScope($tokenInfo, 'revoke_client');

                $commonName = InputValidation::commonName($commonName);

                $this->logger->info('revoking certificate', array('cn' => $commonName));

                return $this->revokeCert($commonName);
            }
        );

        $service->get(
            '/certificate/:userId',
            function ($userId, TokenInfo $tokenInfo) {
                self::requireScope($tokenInfo, 'list');

                $userId = InputValidation::userId($userId);

                return $this->getCertList($userId);
            }
        );

        $service->get(
            '/certificate/',
            function (TokenInfo $tokenInfo) {
                self::requireScope($tokenInfo, 'list');

                return $this->getCertList();
            }
        );

        $service->get(
            '/ca.crl',
            function (TokenInfo $tokenInfo) {
                self::requireScope($tokenInfo, 'crl');

                return $this->getCrl();
            }
        );
    }

    public function generateCert($commonName, $isServer = false)
    {
        if (false !== $this->ca->getCertInfo($commonName)) {
            throw new BadRequestException('certificate already exists');
        }

        if ($isServer) {
            $certInfo = $this->ca->generateServerCert($commonName, 2048);
        } else {
            $certInfo = $this->ca->generateClientCert($commonName);
        }

        $certInfo['cn'] = $commonName;
        $certInfo['ca'] = $this->ca->getCaCert();
        $certInfo['ta'] = $this->ca->getTlsAuthKey();

        $response = new JsonResponse(201);
        $response->setBody(
            [
                'ok' => true,
                'certificate' => $certInfo,
            ]
        );

        return $response;
    }

    public function revokeCert($commonName)
    {
        if (false === $certInfo = $this->ca->getCertInfo($commonName)) {
            throw new NotFoundException('certificate does not exist');
        }

        if ('V' !== $certInfo['state']) {
            throw new BadRequestException('certificate is not active');
        }

        $this->ca->revokeClientCert($commonName);

        $response = new JsonResponse(200);
        $response->setBody(['ok' => true]);

        return $response;
    }

    public function getCertList($userId = null)
    {
        if (is_null($userId)) {
            $certList = $this->ca->getCertList();
        } else {
            $certList = $this->ca->getUserCertList($userId);
        }

        $response = new JsonResponse(200);
        $response->setBody(
            array(
                'ok' => true,
                'items' => $certList,
            )
        );

        return $response;
    }

    public function getCrl()
    {
        $response = new Response(200, 'application/pkix-crl');
        $response->setHeader('Last-Modified', gmdate('r', $this->ca->getCrlLastModifiedTime()));
        $response->setBody($this->ca->getCrl());

        return $response;
    }

    private static function requireScope(TokenInfo $tokenInfo, $requiredScope)
    {
        if (!$tokenInfo->getScope()->hasScope($requiredScope)) {
            throw new ForbiddenException('insufficient_scope', sprintf('"%s" scope required', $requiredScope));
        }
    }
}
