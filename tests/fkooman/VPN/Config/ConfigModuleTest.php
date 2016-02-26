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
namespace fkooman\VPN\Config;

require_once __DIR__.'/Test/TestTemplateManager.php';
require_once __DIR__.'/Test/TestCa.php';

use fkooman\VPN\Config\Test\TestTemplateManager;
use fkooman\VPN\Config\Test\TestCa;
use PHPUnit_Framework_TestCase;
use fkooman\Http\Request;
use fkooman\Rest\Plugin\Authentication\AuthenticationPlugin;
use fkooman\Rest\Plugin\Authentication\Dummy\DummyAuthentication;
use Psr\Log\NullLogger;
use fkooman\Rest\Service;

class ConfigModuleTest extends PHPUnit_Framework_TestCase
{
    /** @var \fkooman\Rest\Service */
    private $service;

    public function setUp()
    {
        $this->service = new Service();
        $this->service->addModule(
            new ConfigModule(
                new TestCa(),
                new TestTemplateManager(),
                new NullLogger()
            )
        );
        $authenticationPlugin = new AuthenticationPlugin();
        $authenticationPlugin->register(new DummyAuthentication('test-user'), 'api');
        $this->service->getPluginRegistry()->registerDefaultPlugin($authenticationPlugin);
    }

    public function testGenerateCert()
    {
        $this->assertSame(
'{"client":{"cn":"foobar","timestamp":1234567890,"valid_from":1234567890,"valid_to":2345678901,"ca":"Ca","cert":"ClientCert for foobar","key":"ClientKey for foobar","ta":"TlsAuthKey"}}',
            $this->makeRequest('POST', '/config/', ['commonName' => 'foobar'])
        );
    }

    public function testGenerateCertAsJson()
    {
        $this->assertSame(
            [
                'cn' => 'foobar',
                'timestamp' => 1234567890,
                'valid_from' => 1234567890,
                'valid_to' => 2345678901,
                'ca' => 'Ca',
                'cert' => 'ClientCert for foobar',
                'key' => 'ClientKey for foobar',
                'ta' => 'TlsAuthKey',
            ],
            $this->makeRequest('POST', '/config/', ['commonName' => 'foobar'], ['HTTP_ACCEPT' => 'application/json'])
        );
    }

    public function testRevokeAlreadyRevokedCert()
    {
        $this->assertSame(
            [
                'ok' => false,
            ],
            $this->makeRequest('DELETE', '/config/already_revoked')
        );
    }

    public function testRevokeNonExistingCert()
    {
        $this->assertSame(
            [
                // XXX should be ok => false instead??? why error here?
                'error' => 'certificate with this common name does not exist',
            ],
            $this->makeRequest('DELETE', '/config/missing')
        );
    }

    public function testGenerateExistingCert()
    {
        $this->assertSame(
            [
                'error' => 'certificate with this common name already exists',
            ],
            $this->makeRequest('POST', '/config/', ['commonName' => 'foo'])
        );
    }

    public function testGetCrl()
    {
        $this->assertSame(
            'Crl',
            $this->makeRequest('GET', '/ca.crl')
        );
    }

    public function testGetCertList()
    {
        $this->assertSame(
            [
                'ok' => true,
                'items' => [
                    'foo_bar',
                    'foo_abc',
                    'abc_def',
                ],
            ],
            $this->makeRequest('GET', '/config')
        );
    }

    public function testGetCertListForUser()
    {
        $this->assertSame(
            [
                'ok' => true,
                'items' => [
                    'foo_bar',
                    'foo_abc',
                ],
            ],
            $this->makeRequest('GET', '/config', ['userId' => 'foo'])
        );
    }

    private function makeRequest($requestMethod, $requestUri, array $queryBody = [], array $additionalHeaders = [])
    {
        if ('GET' === $requestMethod || 'DELETE' === $requestMethod) {
            return $this->service->run(
                new Request(
                    array_merge(
                        array(
                            'SERVER_NAME' => 'www.example.org',
                            'SERVER_PORT' => 80,
                            'REQUEST_METHOD' => $requestMethod,
                            'REQUEST_URI' => sprintf('%s?%s', $requestUri, http_build_query($queryBody)),
                            'PATH_INFO' => $requestUri,
                            'QUERY_STRING' => http_build_query($queryBody),
                        ),
                        $additionalHeaders
                    )
                )
            )->getBody();
        } else {
            // POST
            return $this->service->run(
                new Request(
                    array_merge(
                        array(
                            'SERVER_NAME' => 'www.example.org',
                            'SERVER_PORT' => 80,
                            'REQUEST_METHOD' => $requestMethod,
                            'REQUEST_URI' => $requestUri,
                            'PATH_INFO' => $requestUri,
                            'QUERY_STRING' => '',
                        ),
                        $additionalHeaders
                    ),
                    $queryBody
                )
            )->getBody();
        }
    }
}
