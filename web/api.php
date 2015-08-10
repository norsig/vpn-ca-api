<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use fkooman\Rest\Plugin\Authentication\Basic\BasicAuthentication;
use fkooman\Ini\IniReader;
use fkooman\VPN\EasyRsa;
use fkooman\VPN\PdoStorage;
use fkooman\VPN\CertService;
use fkooman\Tpl\Twig\TwigTemplateManager;

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

$templateManager = new TwigTemplateManager(
    array(
        dirname(__DIR__).'/views',
        dirname(__DIR__).'/config/views',
    ),
    null
);

$service = new CertService($easyRsa, $templateManager, $iniReader->v('clients', 'remotes'));
$service->getPluginRegistry()->registerDefaultPlugin(
    new BasicAuthentication(
        function ($userId) use ($iniReader) {
            return $userId === $iniReader->v('authUser') ? $iniReader->v('authPass') : false;
        },
        array('realm' => 'VPN Configuration Service')
    )
);
$service->run()->send();
