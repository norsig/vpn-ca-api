<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use fkooman\Rest\Service;
use fkooman\Http\Request;
use fkooman\Http\IncomingHttpRequest;
use fkooman\Http\Exception\HttpException;
use fkooman\Config\Config;

try {
    $config = Config::fromIniFile(
        dirname(__DIR__)."/config/config.ini"
    );

    $easyRsa = new EasyRsa($config->getValue('easyRsaConfigPath'));

    $request = Request::fromIncomingHttpRequest(new IncomingHttpRequest());
    $service = new Service($request);
    $service->delete('/:commonName', function ($commonName) {
        // revoke
    });
    $service->post('/:commonName', function ($commonName) {
        // generate
    });
    $service->get('/:commonName', function ($commonName) {
        // get
    });
    $service->run();
} catch (Exception $e) {
    if ($e instanceof HttpException) {
    }
}
