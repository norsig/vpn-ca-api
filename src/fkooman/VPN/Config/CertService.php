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

class CertService extends Service
{
    /** @var CaInterface */
    private $ca;

    /** @var \fkooman\Tpl\TemplateManagerInterface */
    private $templateManager;

    public function __construct(CaInterface $ca, TemplateManagerInterface $templateManager)
    {
        parent::__construct();

        $this->ca = $ca;
        $this->templateManager = $templateManager;
        $this->registerRoutes();
    }

    public function registerRoutes()
    {
        /* DELETE */
        $this->delete(
            '/config/:commonName',
            function ($commonName) {
                return $this->revokeCert($commonName);
            }
        );

        /* POST */
        $this->post(
            '/config/',
            function (Request $request) {
                if (0 === strpos($request->getHeader('Accept'), 'application/json')) {
                    return $this->generateCert(
                        $request->getPostParameter('commonName'),
                        true
                    );
                } else {
                    return $this->generateCert(
                        $request->getPostParameter('commonName')
                    );
                }
            }
        );

        /* GET */
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
        self::validateCommonName($commonName);

        if ($this->ca->hasCert($commonName)) {
            throw new BadRequestException('certificate with this common name already exists');
        }

        $certKey = $this->ca->generateClientCert($commonName);

        $configData = array(
            'cn' => $commonName,
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
        self::validateCommonName($commonName);

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

    public static function validateCommonName($commonName)
    {
        if (0 === preg_match('/^[a-zA-Z0-9-_.@]+$/', $commonName)) {
            throw new BadRequestException('invalid common name syntax');
        }
    }
}
