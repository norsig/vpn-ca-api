<?php

/**
 * Copyright 2015 François Kooman <fkooman@tuxed.net>.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
require_once dirname(__DIR__).'/vendor/autoload.php';

use fkooman\Rest\Plugin\Authentication\AuthenticationPlugin;
use fkooman\Rest\Plugin\Authentication\Basic\BasicAuthentication;
use fkooman\Ini\IniReader;
use fkooman\VPN\CertService;
use fkooman\Tpl\Twig\TwigTemplateManager;

try {
    $iniReader = IniReader::fromFile(
        dirname(__DIR__).'/config/config.ini'
    );

    $caBackend = $iniReader->v('caBackend', false, 'EasyRsa2');
    $caBackendClass = sprintf('\\fkooman\\VPN\\%s', $caBackend);
    $ca = new $caBackendClass($iniReader->v($caBackend));

    $templateManager = new TwigTemplateManager(
        array(
            dirname(__DIR__).'/views',
            dirname(__DIR__).'/config/views',
        ),
        null
    );

    $service = new CertService($ca, $templateManager);

    $basicAuthentication = new BasicAuthentication(
        function ($userId) use ($iniReader) {
            return $userId === $iniReader->v('authUser') ? $iniReader->v('authPass') : false;
        },
        array('realm' => 'VPN Configuration Service')
    );
    $authenticationPlugin = new AuthenticationPlugin();
    $authenticationPlugin->register($basicAuthentication, 'api');
    $service->getPluginRegistry()->registerDefaultPlugin($authenticationPlugin);
    $service->run()->send();
} catch (Exception $e) {
    error_log($e->getMessage());
    die(sprintf('ERROR: %s', $e->getMessage()));
}
