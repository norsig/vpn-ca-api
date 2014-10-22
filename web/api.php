<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use fkooman\Http\Exception\HttpException;
use fkooman\Http\Exception\InternalServerErrorException;
use fkooman\Config\Config;
use fkooman\VPN\EasyRsa;
use fkooman\VPN\PdoStorage;
use fkooman\VPN\CertService;

set_error_handler(
    function ($errno, $errstr, $errfile, $errline) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
);

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

    $certService = new CertService($config, $easyRsa);
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
