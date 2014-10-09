<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use fkooman\Rest\Service;
use fkooman\Http\Request;
use fkooman\Http\IncomingRequest;
use fkooman\Http\Exception\HttpException;
use fkooman\Http\Exception\InternalServerErrorException;
use fkooman\Config\Config;
use fkooman\VPN\EasyRsa;
use fkooman\VPN\PdoStorage;
use fkooman\VPN\CertService;

try {
    $config = Config::fromIniFile(
        dirname(__DIR__)."/config/config.ini"
    );

    $db = new PDO(
        $config->s('PdoStorage')->l('dsn', true),
        $config->s('PdoStorage')->l('username', false),
        $config->s('PdoStorage')->l('password', false)
    );

    $db = new PdoStorage($db);

    // FIXME: maybe initialize EasyRsa from within CertService?
    $easyRsa = new EasyRsa($config->getValue('easyRsaConfigPath', true), $db);
    $certService = new CertService($config, $easyRsa);

    $request = Request::fromIncomingRequest(new IncomingRequest());
    $service = new Service($request);
    $service->delete('/:commonName', function ($commonName) use ($certService) {
        return $certService->revokeCert($commonName);
    });
    $service->post('/:commonName', function ($commonName) use ($certService) {
        return $certService->generateCert($commonName);
    });
    $service->get('/crl', function () use ($certService) {
        return $certService->getCrl();
    });
    $service->run()->sendResponse();
} catch (Exception $e) {
    if ($e instanceof HttpException) {
        $response = $e->getResponse();
    } else {
        // we catch all other (unexpected) exceptions and return a 500
        $e = new InternalServerErrorException($e->getMessage());
        $response = $e->getResponse();
    }
    $response->sendResponse();
}
