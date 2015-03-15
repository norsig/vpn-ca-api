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
    $easyRsa = new EasyRsa($iniReader->v('easyRsaConfigPath'), $pdoStorage, $iniReader->v('ca', 'key_size'));

    $basicAuthenticationPlugin = new BasicAuthentication(
        function ($userId) use ($iniReader) {
            return $userId === $iniReader->v('authUser') ? $iniReader->v('authPass') : false;
        },
        'VPN Configuration Service'
    );

    $certService = new CertService($easyRsa, $iniReader->v('clients', 'remotes'));
    $certService->registerOnMatchPlugin($basicAuthenticationPlugin);
    $certService->run()->sendResponse();
} catch (Exception $e) {
    error_log($e->getMessage());
    CertService::handleException($e)->sendResponse();
}
