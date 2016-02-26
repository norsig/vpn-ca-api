<?php

/**
 * Copyright 2016 François Kooman <fkooman@tuxed.net>.
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

require_once __DIR__.'/Test/TestCa.php';

use fkooman\VPN\Config\Test\TestCa;
use PHPUnit_Framework_TestCase;
use fkooman\Http\Request;
use fkooman\Rest\Plugin\Authentication\AuthenticationPlugin;
use fkooman\Rest\Plugin\Authentication\Bearer\BearerAuthentication;
use fkooman\Rest\Plugin\Authentication\Bearer\ArrayBearerValidator;
use Psr\Log\NullLogger;
use fkooman\Rest\Service;

class CertificateModuleTest extends PHPUnit_Framework_TestCase
{
    /** @var \fkooman\Rest\Service */
    private $service;

    public function setUp()
    {
        $this->service = new Service();
        $this->service->addModule(
            new CertificateModule(
                new TestCa(),
                new NullLogger()
            )
        );
        $authenticationPlugin = new AuthenticationPlugin();
        $authenticationPlugin->register(
            new BearerAuthentication(
                new ArrayBearerValidator(
                    [
                        'vpn-user-portal' => [
                            'token' => 'abcdef',
                            'scope' => 'issue_client revoke_client list',
                        ],
                        'vpn-admin-portal' => [
                            'token' => 'fedcba',
                            'scope' => 'list',
                        ],
                        'vpn-server-api' => [
                            'token' => 'aabbcc',
                            'scope' => 'issue_server crl',
                        ],
                    ]
                )
            ),
            'api');
        $this->service->getPluginRegistry()->registerDefaultPlugin($authenticationPlugin);
    }

    public function testGenerateCert()
    {
        $this->assertSame(
            [
                'ok' => true,
                'certificate' => [
                    'cert' => 'ClientCert for foobar',
                    'key' => 'ClientKey for foobar',
                    'valid_from' => 1234567890,
                    'valid_to' => 2345678901,
                    'cn' => 'foobar',
                    'ca' => 'Ca',
                    'ta' => 'TlsAuthKey',
                ],
            ],
            $this->makeRequest('abcdef', 'POST', '/certificate/', ['common_name' => 'foobar', 'cert_type' => 'client'])
        );
    }

    public function testGenerateServerCert()
    {
        $this->assertSame(
            [
                'ok' => true,
                'certificate' => [
                    'cert' => 'ServerCert for vpn.example',
                    'key' => 'ServerCert for vpn.example',
                    'dh' => 'ServerDh for vpn.example',
                    'valid_from' => 1234567890,
                    'valid_to' => 2345678901,
                    'cn' => 'vpn.example',
                    'ca' => 'Ca',
                    'ta' => 'TlsAuthKey',
                ],
            ],
            $this->makeRequest('aabbcc', 'POST', '/certificate/', ['common_name' => 'vpn.example', 'cert_type' => 'server'])
        );
    }

    public function testRevokeAlreadyRevokedCert()
    {
        $this->assertSame(
            [
                'error' => 'certificate is not active',
            ],
            $this->makeRequest('abcdef', 'DELETE', '/certificate/foo_foo')
        );
    }

    public function testRevokeNonExistingCert()
    {
        $this->assertSame(
            [
                'error' => 'certificate does not exist',
            ],
            $this->makeRequest('abcdef', 'DELETE', '/certificate/missing')
        );
    }

    public function testGenerateExistingCert()
    {
        $this->assertSame(
            [
                'error' => 'certificate already exists',
            ],
            $this->makeRequest('abcdef', 'POST', '/certificate/', ['common_name' => 'foo_foo', 'cert_type' => 'client'])
        );
    }

    public function testGetCrl()
    {
        $this->assertSame(
            'Crl',
            $this->makeRequest('aabbcc', 'GET', '/ca.crl')
        );
    }

    public function testGetCertList()
    {
        $this->assertSame(
            [
                'ok' => true,
                'items' => [
                    [
                        'user_id' => 'foo',
                        'name' => 'foo',
                        'state' => 'R',
                        'exp' => 1487771854,
                        'rev' => 1456236048,
                    ],
                    [
                        'user_id' => 'bar',
                        'name' => 'test',
                        'state' => 'V',
                        'exp' => 1487771884,
                        'rev' => false,
                    ],
                    [
                        'user_id' => 'bar',
                        'name' => 'Test',
                        'state' => 'V',
                        'exp' => 1487779835,
                        'rev' => false,
                    ],
                    [
                        'user_id' => 'bar',
                        'name' => 'lkjlkjlkj',
                        'state' => 'R',
                        'exp' => 1487863193,
                        'rev' => 1456327200,
                    ],
                    [
                        'user_id' => 'foo',
                        'name' => 'a_b_c_d_e',
                        'state' => 'E',
                        'exp' => 1455442030,
                        'rev' => false,
                    ],
                ],
            ],
            $this->makeRequest('abcdef', 'GET', '/certificate/')
        );
    }

    public function testGetCertListForUser()
    {
        $this->assertSame(
            [
                'ok' => true,
                'items' => [
                    [
                        'user_id' => 'foo',
                        'name' => 'foo',
                        'state' => 'R',
                        'exp' => 1487771854,
                        'rev' => 1456236048,
                    ],
                    [
                        'user_id' => 'foo',
                        'name' => 'a_b_c_d_e',
                        'state' => 'E',
                        'exp' => 1455442030,
                        'rev' => false,
                    ],
                ],
            ],
            $this->makeRequest('abcdef', 'GET', '/certificate/foo')
        );
    }

    private function makeRequest($bearerToken, $requestMethod, $requestUri, array $queryBody = [], array $additionalHeaders = [])
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
                            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $bearerToken),
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
                            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $bearerToken),
                        ),
                        $additionalHeaders
                    ),
                    $queryBody
                )
            )->getBody();
        }
    }
}
