<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use fkooman\Http\Exception\HttpException;
use fkooman\Http\Exception\InternalServerErrorException;
use fkooman\Rest\Plugin\Basic\BasicAuthentication;
use fkooman\Ini\IniReader;
use fkooman\VPN\EasyRsa;
use fkooman\VPN\PdoStorage;
use fkooman\VPN\CertService;

set_error_handler(
    function ($errno, $errstr, $errfile, $errline) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
);

try {
    $iniReader = IniReader::fromFile(
        dirname(__DIR__).'/config/config.ini'
    );

    $pdo = new PDO(
        $iniReader->v('PdoStorage', 'dsn'),
        $iniReader->v('PdoStorage', 'username', false),
        $iniReader->v('PdoStorage', 'password', false)
    );

    $pdoStorage = new PdoStorage($pdo);
    $easyRsa = new EasyRsa($iniReader->v('easyRsaConfigPath'), $pdoStorage);

    $basicAuthenticationPlugin = new BasicAuthentication(
        $iniReader->v('authUser'),
        $iniReader->v('authPass'),
        'VPN Configuration Service'
    );

    $certService = new CertService($easyRsa, $iniReader->v('clients', 'remotes'));
    $certService->registerBeforeEachMatchPlugin($basicAuthenticationPlugin);
    $certService->run()->sendResponse();
} catch (Exception $e) {
    if ($e instanceof HttpException) {
        $response = $e->getJsonResponse();
    } else {
        // we catch all other (unexpected) exceptions and return a 500
        error_log($e->getTraceAsString());
        $e = new InternalServerErrorException($e->getMessage());
        $response = $e->getJsonResponse();
    }
    $response->sendResponse();
}
