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
use fkooman\VPN\Config\CertService;
use fkooman\Tpl\Twig\TwigTemplateManager;
use fkooman\Http\Exception\InternalServerErrorException;
use fkooman\VPN\Config\SimpleError;

SimpleError::register();

try {
    $iniReader = IniReader::fromFile(
        dirname(__DIR__).'/config/config.ini'
    );

    $caBackend = $iniReader->v('caBackend', false, 'EasyRsa2');
    $caBackendClass = sprintf('\\fkooman\\VPN\\Config\\%s', $caBackend);
    $ca = new $caBackendClass($iniReader->v($caBackend));

    $templateManager = new TwigTemplateManager(
        array(
            dirname(__DIR__).'/views',
            dirname(__DIR__).'/config/views',
        ),
        null
    );

    $service = new CertService($ca, $templateManager);

    $auth = new BasicAuthentication(
        function ($userId) use ($iniReader) {
            $userList = $iniReader->v('BasicAuthentication');
            if (!array_key_exists($userId, $userList)) {
                return false;
            }

            return $userList[$userId];
        },
        array('realm' => 'VPN Config API')
    );

    $authenticationPlugin = new AuthenticationPlugin();
    $authenticationPlugin->register($auth, 'api');
    $service->getPluginRegistry()->registerDefaultPlugin($authenticationPlugin);
    $service->run()->send();
} catch (Exception $e) {
    // internal server error
    error_log($e->__toString());
    $e = new InternalServerErrorException($e->getMessage());
    $e->getJsonResponse()->send();
}
