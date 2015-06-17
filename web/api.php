<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use fkooman\Rest\Plugin\Basic\BasicAuthentication;
use fkooman\Ini\IniReader;
use fkooman\VPN\EasyRsa;
use fkooman\VPN\PdoStorage;
use fkooman\VPN\CertService;
use fkooman\Rest\ExceptionHandler;
use fkooman\Rest\PluginRegistry;

ExceptionHandler::register();

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

$pluginRegistry = new PluginRegistry();
$pluginRegistry->registerDefaultPlugin(
    new BasicAuthentication(
        function ($userId) use ($iniReader) {
            return $userId === $iniReader->v('authUser') ? $iniReader->v('authPass') : false;
        },
        'VPN Configuration Service'
    )
);

$service = new CertService($easyRsa, $iniReader->v('clients', 'remotes'));
$service->setPluginRegistry($pluginRegistry);
$service->run()->send();
