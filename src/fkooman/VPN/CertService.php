<?php

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
    /** @var EasyRsa */
    private $easyRsa;

    /** @var \fkooman\Tpl\TemplateManagerInterface */
    private $templateManager;

    /** @var array */
    private $remotes;

    public function __construct(EasyRsa $easyRsa, TemplateManagerInterface $templateManager, array $remotes)
    {
        parent::__construct();

        $this->easyRsa = $easyRsa;
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

        if ($this->easyRsa->hasCert($commonName)) {
            throw new ForbiddenException('certificate with this common name already exists');
        }

        $certKey = $this->easyRsa->generateClientCert($commonName);

        $configData = array(
            'cn' => $commonName,
            'ca' => $this->easyRsa->getCaCert(),
            'cert' => $certKey['cert'],
            'key' => $certKey['key'],
            'ta' => $this->easyRsa->getTlsAuthKey(),
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

        if (!$this->easyRsa->hasCert($commonName)) {
            throw new NotFoundException('certificate with this common name does not exist');
        }

        $this->easyRsa->revokeClientCert($commonName);

        return new Response(200);
    }

    public function getCrl()
    {
        $response = new Response(200, 'application/pkix-crl');

        $crlData = $this->easyRsa->getCrl();
        if (null !== $crlData) {
            $response->setHeader('Last-Modified', $this->easyRsa->getCrlLastModifiedTime());
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
