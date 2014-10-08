<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use fkooman\Rest\Service;
use fkooman\Http\Request;
use fkooman\Http\IncomingRequest;
use fkooman\Http\Exception\HttpException;
use fkooman\Config\Config;
use fkooman\VPN\ConfigGenerator;
use fkooman\VPN\EasyRsa;
use fkooman\Http\Response;
use fkooman\Http\JsonResponse;
use fkooman\VPN\PdoStorage;

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
    $easyRsa = new EasyRsa($config->getValue('easyRsaConfigPath', true), $db);

    $request = Request::fromIncomingRequest(new IncomingRequest());
    $service = new Service($request);
    $service->delete('/:commonName', function ($commonName) use ($easyRsa) {
        $easyRsa->revokeClientCert($commonName);
        // revoke
        $response = new JsonResponse();
        $response->setContent(array("status" => "ok"));

        return $response;
    });
    $service->post('/:commonName', function ($commonName) use ($easyRsa, $config) {
        // generate
        $configGenerator = new ConfigGenerator($easyRsa, $config->s('remotes')->toArray());

        $response = new Response(201, "text/plain");
        $response->setContent($configGenerator->generateClientConfig($commonName));

        return $response;
    });
    $service->run()->sendResponse();
} catch (Exception $e) {
    die($e->getMessage());
#    if ($e instanceof HttpException) {
#    }
}
