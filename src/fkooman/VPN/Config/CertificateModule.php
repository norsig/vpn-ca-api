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
                if (!$tokenInfo->getScope()->hasScope('issue_client')) {
                    throw new ForbiddenException('insufficient_scope', 'issue_client');
                }

                $commonName = InputValidation::commonName(
                    $request->getPostParameter('common_name'),
                    true // REQUIRED
                );

                $this->logger->info('issueing certificate', array('cn' => $commonName));

                return $this->generateCert($commonName);
            }
        );

        $service->delete(
            '/certificate/:commonName',
            function ($commonName, TokenInfo $tokenInfo) {
                if (!$tokenInfo->getScope()->hasScope('revoke_client')) {
                    throw new ForbiddenException('insufficient_scope', 'revoke_client');
                }

                $commonName = InputValidation::commonName(
                    $commonName,
                    true // REQUIRED
                );

                $this->logger->info('revoking certificate', array('cn' => $commonName));

                return $this->revokeCert($commonName);
            }
        );

        $service->get(
            '/certificate/:userId',
            function ($userId, TokenInfo $tokenInfo) {
                if (!$tokenInfo->getScope()->hasScope('list')) {
                    throw new ForbiddenException('insufficient_scope', 'list');
                }

                $userId = InputValidation::userId(
                    $userId,
                    true // REQUIRED
                );

                return $this->getCertList($userId);
            }
        );

        $service->get(
            '/certificate/',
            function (TokenInfo $tokenInfo) {
                if (!$tokenInfo->getScope()->hasScope('list')) {
                    throw new ForbiddenException('insufficient_scope', 'list');
                }

                return $this->getCertList();
            }
        );

        $service->get(
            '/ca.crl',
            function (TokenInfo $tokenInfo) {
                if (!$tokenInfo->getScope()->hasScope('crl')) {
                    throw new ForbiddenException('insufficient_scope', 'crl');
                }

                return $this->getCrl();
            }
        );
    }

    public function generateCert($commonName)
    {
        if (false !== $this->ca->getCertInfo($commonName)) {
            throw new BadRequestException('certificate already exists');
        }

        $certKey = $this->ca->generateClientCert($commonName);

        $certData = array(
            'cn' => $commonName,
            'valid_from' => $certKey['valid_from'],
            'valid_to' => $certKey['valid_to'],
            'ca' => $this->ca->getCaCert(),
            'cert' => $certKey['cert'],
            'key' => $certKey['key'],
            'ta' => $this->ca->getTlsAuthKey(),
        );

        $response = new JsonResponse(201);
        $response->setBody(
            [
                'ok' => true,
                'certificate' => $certData,
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
}
