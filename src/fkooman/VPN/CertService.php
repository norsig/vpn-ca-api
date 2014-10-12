<?php

namespace fkooman\VPN;

use fkooman\Config\Config;
use fkooman\Rest\Service;
use fkooman\Rest\Plugin\BasicAuthentication;
use fkooman\Http\Request;
use fkooman\Http\Response;
use fkooman\Http\Exception\BadRequestException;
use fkooman\Http\Exception\NotFoundException;
use fkooman\Http\Exception\ForbiddenException;

class CertService extends Service
{
    /** @var fkooman\Config\Config */
    private $config;

    /** @var fkooman\VPN\EasyRsa */
    private $easyRsa;

    public function __construct(Config $config, EasyRsa $easyRsa)
    {
        parent::__construct();

        $this->config = $config;
        $this->easyRsa = $easyRsa;

        $basicAuthenticationPlugin = new BasicAuthentication(
            $config->l('authUser', true),
            $config->l('authPass', true),
            'vpn-cert-service'
        );

        $this->registerBeforeEachMatchPlugin($basicAuthenticationPlugin);

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
            array('fkooman\Rest\Plugin\BasicAuthentication')
        );
    }

    public function generateCert($commonName)
    {
        $this->validateCommonName($commonName);

        if ($this->easyRsa->hasCert($commonName)) {
            throw new ForbiddenException("certificate with this common name already exists");
        }

        $certKey = $this->easyRsa->generateClientCert($commonName);

        $configData = array(
            'cn' => $commonName,
            'ca' => $this->easyRsa->getCaCert(),
            'cert' => $certKey['cert'],
            'key' => $certKey['key'],
            'ta' => $this->easyRsa->getTlsAuthKey(),
            'remotes' => $this->config->s('clients', true)->s('remotes', true)->toArray(),
        );
        $configGenerator = new ConfigGenerator($configData);
        $response = new Response(201, "application/x-openvpn-profile");
        $response->setContent($configGenerator->getConfig());

        return $response;
    }

    public function revokeCert($commonName)
    {
        $this->validateCommonName($commonName);

        if (!$this->easyRsa->hasCert($commonName)) {
            throw new NotFoundException("certificate with this common name does not exist");
        }

        $this->easyRsa->revokeClientCert($commonName);

        return new Response(200);
    }

    public function getCrl()
    {
        $response = new Response(200, 'application/pkix-crl');

        $crlData = $this->easyRsa->getCrl();
        if (null !== $crlData) {
            $response->setContent($crlData);
        }

        return $response;
    }

    private function validateCommonName($commonName)
    {
        if (0 === preg_match('/^[a-zA-Z0-9-_.@]+$/', $commonName)) {
            throw new BadRequestException("invalid common name syntax");
        }
    }
}
