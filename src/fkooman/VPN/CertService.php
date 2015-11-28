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
namespace fkooman\VPN;

use fkooman\Rest\Service;
use fkooman\Http\Request;
use fkooman\Http\Response;
use fkooman\Http\Exception\BadRequestException;
use fkooman\Http\Exception\NotFoundException;
use fkooman\Http\Exception\ForbiddenException;
use fkooman\Tpl\TemplateManagerInterface;

class CertService extends Service
{
    /** @var CaInterface */
    private $ca;

    /** @var \fkooman\Tpl\TemplateManagerInterface */
    private $templateManager;

    /** @var array */
    private $remotes;

    public function __construct(CaInterface $ca, TemplateManagerInterface $templateManager, array $remotes)
    {
        parent::__construct();

        $this->ca = $ca;
        $this->templateManager = $templateManager;
        $this->remotes = $remotes;

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
                return $this->generateCert(
                    $request->getPostParameter('commonName')
                );
            }
        );

        /* GET */
        $this->get(
            '/ca.crl',
            function () {
                return $this->getCrl();
            },
            array(
                'fkooman\Rest\Plugin\Authentication\Basic\BasicAuthentication' => array('enabled' => false),
            )
        );
    }

    public function generateCert($commonName)
    {
        self::validateCommonName($commonName);

        if ($this->ca->hasCert($commonName)) {
            throw new ForbiddenException('certificate with this common name already exists');
        }

        $certKey = $this->ca->generateClientCert($commonName);

        $configData = array(
            'cn' => $commonName,
            'ca' => $this->ca->getCaCert(),
            'cert' => $certKey['cert'],
            'key' => $certKey['key'],
            'ta' => $this->ca->getTlsAuthKey(),
            'remotes' => $this->remotes,
        );

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
