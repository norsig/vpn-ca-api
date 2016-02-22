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

use fkooman\VPN\Config\Test\TestTemplateManager;
use PHPUnit_Framework_TestCase;
use fkooman\Http\Request;
use fkooman\Rest\Plugin\Authentication\AuthenticationPlugin;
use fkooman\Rest\Plugin\Authentication\Dummy\DummyAuthentication;
use Psr\Log\NullLogger;

class CertServiceTest extends PHPUnit_Framework_TestCase
{
    private $certService;

    public function setUp()
    {
        $ca = new NullCa();
        $tpl = new TestTemplateManager();
        $this->certService = new CertService($ca, $tpl, new NullLogger());
        $authenticationPlugin = new AuthenticationPlugin();
        $authenticationPlugin->register(new DummyAuthentication('test-user'), 'api');
        $this->certService->getPluginRegistry()->registerDefaultPlugin($authenticationPlugin);
    }

    public function testGenerateCert()
    {
        $request = new Request(
            array(
                'SERVER_NAME' => 'ca.example.org',
                'SERVER_PORT' => 80,
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/api.php/config/',
                'SCRIPT_NAME' => '/api.php',
                'PATH_INFO' => '/config/',
                'REQUEST_METHOD' => 'POST',
            ),
            array(
                'commonName' => 'foobar',
            )
        );

        $response = $this->certService->run($request);
        $this->assertSame(
            array(
                'HTTP/1.1 201 Created',
                'Content-Type: application/x-openvpn-profile',
                'Content-Length: 183',
                '',
                '{"client":{"cn":"foobar","timestamp":1234567890,"valid_from":1234567890,"valid_to":2345678901,"ca":"Ca","cert":"ClientCert for foobar","key":"ClientKey for foobar","ta":"TlsAuthKey"}}',

            ),
            $response->toArray()
        );
    }

    public function testGenerateCertAsJson()
    {
        $request = new Request(
            array(
                'SERVER_NAME' => 'ca.example.org',
                'SERVER_PORT' => 80,
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/api.php/config/',
                'SCRIPT_NAME' => '/api.php',
                'PATH_INFO' => '/config/',
                'REQUEST_METHOD' => 'POST',
                'HTTP_ACCEPT' => 'application/json',
            ),
            array(
                'commonName' => 'foobar',
            )
        );

        $response = $this->certService->run($request);
        $this->assertSame(
            array(
                'HTTP/1.1 201 Created',
                'Content-Type: application/json',
                'Content-Length: 172',
                '',
                '{"cn":"foobar","timestamp":1234567890,"valid_from":1234567890,"valid_to":2345678901,"ca":"Ca","cert":"ClientCert for foobar","key":"ClientKey for foobar","ta":"TlsAuthKey"}',
            ),
            $response->toArray()
        );
    }

    public function testRevokeExistingCert()
    {
        $request = new Request(
            array(
                'SERVER_NAME' => 'ca.example.org',
                'SERVER_PORT' => 80,
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/api.php/config/foo',
                'SCRIPT_NAME' => '/api.php',
                'PATH_INFO' => '/config/already_revoked',
                'REQUEST_METHOD' => 'DELETE',
            )
        );

        $response = $this->certService->run($request);
        $this->assertSame(
            array(
                'HTTP/1.1 200 OK',
                'Content-Type: application/json',
                'Content-Length: 12',
                '',
                '{"ok":false}',
            ),
            $response->toArray()
        );
    }

    public function testRevokeNonExistingCert()
    {
        $request = new Request(
            array(
                'SERVER_NAME' => 'ca.example.org',
                'SERVER_PORT' => 80,
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/api.php/config/missing',
                'SCRIPT_NAME' => '/api.php',
                'PATH_INFO' => '/config/missing',
                'REQUEST_METHOD' => 'DELETE',
            )
        );

        $response = $this->certService->run($request);
        $this->assertSame(
            array(
                'HTTP/1.1 404 Not Found',
                'Content-Type: application/json',
                'Content-Length: 60',
                '',
                '{"error":"certificate with this common name does not exist"}',
            ),
            $response->toArray()
        );
    }

    public function testGenerateExistingCert()
    {
        $request = new Request(
            array(
                'SERVER_NAME' => 'ca.example.org',
                'SERVER_PORT' => 80,
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/api.php/config/',
                'SCRIPT_NAME' => '/api.php',
                'PATH_INFO' => '/config/',
                'REQUEST_METHOD' => 'POST',
            ),
            array(
                'commonName' => 'foo',
            )
        );

        $response = $this->certService->run($request);
        $this->assertSame(
            array(
                'HTTP/1.1 400 Bad Request',
                'Content-Type: application/json',
                'Content-Length: 60',
                '',
                '{"error":"certificate with this common name already exists"}',
            ),
            $response->toArray()
        );
    }

    public function testGetCrl()
    {
        $request = new Request(
            array(
                'SERVER_NAME' => 'ca.example.org',
                'SERVER_PORT' => 80,
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/api.php/ca.crl',
                'SCRIPT_NAME' => '/api.php',
                'PATH_INFO' => '/ca.crl',
                'REQUEST_METHOD' => 'GET',
            )
        );

        $response = $this->certService->run($request);
        $this->assertSame(
            array(
                'HTTP/1.1 200 OK',
                'Content-Type: application/pkix-crl',
                'Last-Modified: Fri, 13 Feb 2009 23:31:30 +0000',
                'Content-Length: 3',
                '',
                'Crl',
            ),
            $response->toArray()
        );
    }
}
