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
use fkooman\Rest\ServiceModuleInterface;
use fkooman\Http\Request;
use fkooman\Http\Response;
use fkooman\Http\Exception\BadRequestException;
use fkooman\Http\Exception\NotFoundException;
use fkooman\Tpl\TemplateManagerInterface;
use fkooman\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use RuntimeException;

class ConfigModule implements ServiceModuleInterface
{
    /** @var CaInterface */
    private $ca;

    /** @var \fkooman\Tpl\TemplateManagerInterface */
    private $templateManager;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    public function __construct(CaInterface $ca, TemplateManagerInterface $templateManager, LoggerInterface $logger)
    {
        $this->ca = $ca;
        $this->templateManager = $templateManager;
        $this->logger = $logger;
    }

    public function init(Service $service)
    {
        // XXX change to "common_name" query parameter
        $service->delete(
            '/config/:commonName',
            function ($commonName) {
                $commonName = InputValidation::commonName(
                    $commonName,
                    true // REQUIRED
                );

                $this->logger->info('revoking config', array('cn' => $commonName));

                return $this->revokeCert($commonName);
            }
        );

        // XXX change to "common_name" POST body
        $service->post(
            '/config/',
            function (Request $request) {
                $commonName = InputValidation::commonName(
                    $request->getPostParameter('commonName'),
                    true // REQUIRED
                );

                $this->logger->info('creating config', array('cn' => $commonName));

                if (false !== strpos($request->getHeader('Accept'), 'application/json')) {
                    return $this->generateCert($commonName, true);
                }

                return $this->generateCert($commonName);
            }
        );

        // XXX change to "user_id" query parameter
        $service->get(
            '/config',
            function (Request $request) {
                $userId = InputValidation::userId(
                    $request->getUrl()->getQueryParameter('userId'),
                    false // OPTIONAL
                );

                return $this->getCertList($userId);
            }
        );

        $service->get(
            '/ca.crl',
            function () {
                return $this->getCrl();
            }
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
            'timestamp' => $certKey['valid_from'],  // XXX: DEPRECATED
            'valid_from' => $certKey['valid_from'],
            'valid_to' => $certKey['valid_to'],
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

        $ok = true;
        try {
            $this->ca->revokeClientCert($commonName);
        } catch (RuntimeException $e) {
            $ok = false;
        }

        $response = new JsonResponse(200);
        $response->setBody(
            array(
                'ok' => $ok,
            )
        );

        return $response;
    }

    public function getCertList($userId)
    {
        $certList = $this->ca->getCertList($userId);

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
        $crlData = $this->ca->getCrl();
        if (false === $crlData || 0 === strlen($crlData)) {
            throw new NotFoundException('crl not available');
        }

        $response = new Response(200, 'application/pkix-crl');
        $response->setHeader('Last-Modified', gmdate('r', $this->ca->getCrlLastModifiedTime()));
        $response->setBody($crlData);

        return $response;
    }
}
