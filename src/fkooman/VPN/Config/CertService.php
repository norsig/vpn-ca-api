<?php

/**
 * Copyright 2015 François Kooman <fkooman@tuxed.net>.
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

use fkooman\Rest\Service;
use fkooman\Http\Request;
use fkooman\Http\Response;
use fkooman\Http\Exception\BadRequestException;
use fkooman\Http\Exception\NotFoundException;
use fkooman\Tpl\TemplateManagerInterface;
use fkooman\Http\JsonResponse;
use fkooman\IO\IO;
use fkooman\Rest\Plugin\Authentication\UserInfoInterface;
use Monolog\Logger;

class CertService extends Service
{
    /** @var CaInterface */
    private $ca;

    /** @var \fkooman\Tpl\TemplateManagerInterface */
    private $templateManager;

    /** @var \Monolog\Logger */
    private $logger;

    /** @var \fkooman\IO\IO */
    private $io;

    public function __construct(CaInterface $ca, TemplateManagerInterface $templateManager, Logger $logger = null, IO $io = null)
    {
        parent::__construct();

        $this->ca = $ca;
        $this->templateManager = $templateManager;
        if (null === $io) {
            $io = new IO();
        }
        $this->logger = $logger;
        $this->io = $io;

        $this->registerRoutes();
    }

    public function registerRoutes()
    {
        $this->delete(
            '/config/:commonName',
            function ($commonName, UserInfoInterface $userInfo) {
                Utils::validateCommonName($commonName);

                $this->logInfo('revoking config', array('api_user' => $userInfo->getUserId(), 'cn' => $commonName));

                return $this->revokeCert($commonName);
            }
        );

        $this->post(
            '/config/',
            function (Request $request, UserInfoInterface $userInfo) {
                $commonName = $request->getPostParameter('commonName');
                Utils::validateCommonName($commonName);

                $this->logInfo('creating config', array('api_user' => $userInfo->getUserId(), 'cn' => $commonName));

                if (false !== strpos($request->getHeader('Accept'), 'application/json')) {
                    return $this->generateCert($commonName, true);
                }

                return $this->generateCert($commonName);
            }
        );

        $this->get(
            '/ca.crl',
            function () {
                return $this->getCrl();
            },
            array(
                'fkooman\Rest\Plugin\Authentication\AuthenticationPlugin' => array(
                    'enabled' => false,
                ),
            )
        );
    }

    public function generateCert($commonName, $returnJson = false)
    {
        if ($this->ca->hasCert($commonName)) {
            throw new BadRequestException('certificate with this common name already exists');
        }

        $certKey = $this->ca->generateClientCert($commonName);

        $configData = array(
            'cn' => $commonName,
            'timestamp' => $this->io->getTime(),
            'ca' => $this->ca->getCaCert(),
            'cert' => $certKey['cert'],
            'key' => $certKey['key'],
            'ta' => $this->ca->getTlsAuthKey(),
        );

        if ($returnJson) {
            $response = new JsonResponse(201);
            $response->setBody($configData);

            return $response;
        }

        $configFile = $this->templateManager->render('client', $configData);
        $response = new Response(201, 'application/x-openvpn-profile');
        $response->setBody($configFile);

        return $response;
    }

    public function revokeCert($commonName)
    {
        if (!$this->ca->hasCert($commonName)) {
            throw new NotFoundException('certificate with this common name does not exist');
        }

        $this->ca->revokeClientCert($commonName);

        return new Response(200);
    }

    public function getCrl()
    {
        $response = new Response(200, 'application/pkix-crl');

        $crlData = $this->ca->getCrl();
        if (null !== $crlData) {
            $response->setHeader('Last-Modified', $this->ca->getCrlLastModifiedTime());
            $response->setBody($crlData);
        }

        return $response;
    }

    private function logInfo($m, array $context)
    {
        if (!is_null($this->logger)) {
            $this->logger->addInfo($m, $context);
        }
    }
}
