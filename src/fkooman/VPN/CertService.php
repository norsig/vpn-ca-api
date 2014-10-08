<?php

namespace fkooman\VPN;

use fkooman\Config\Config;
use fkooman\Http\Exception\BadRequestException;
use fkooman\Http\Exception\NotFoundException;
use fkooman\Http\Exception\ForbiddenException;
use fkooman\Http\Response;

class CertService
{
    /** @var fkooman\Config\Config */
    private $config;

    /** @var fkooman\VPN\EasyRsa */
    private $easyRsa;

    public function __construct(Config $config, EasyRsa $easyRsa)
    {
        $this->config = $config;
        $this->easyRsa = $easyRsa;
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
            'remotes' => $this->config->s('remotes', true)->toArray(),
        );
        $configGenerator = new ConfigGenerator($configData);
        $response = new Response(201, "text/plain");
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
        $response->setContent($this->easyRsa->getCrl());

        return $response;
    }

    private function validateCommonName($commonName)
    {
        if (0 === preg_match('/^[a-zA-Z0-9-_.@]+$/', $commonName)) {
            throw new BadRequestException("invalid common name syntax");
        }
    }
}
