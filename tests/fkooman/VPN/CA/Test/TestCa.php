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

namespace fkooman\VPN\CA\Test;

use fkooman\VPN\CA\CaInterface;
use fkooman\VPN\CA\IndexParser;

class TestCa implements CaInterface
{
    public function generateServerCert($commonName)
    {
        return array(
            'cert' => sprintf('ServerCert for %s', $commonName),
            'key' => sprintf('ServerCert for %s', $commonName),
            'valid_from' => 1234567890,
            'valid_to' => 2345678901,
        );
    }

    public function generateClientCert($commonName)
    {
        return array(
            'cert' => sprintf('ClientCert for %s', $commonName),
            'key' => sprintf('ClientKey for %s', $commonName),
            'valid_from' => 1234567890,
            'valid_to' => 2345678901,
        );
    }

    public function getTlsAuthKey()
    {
        return 'TlsAuthKey';
    }

    public function getCaCert()
    {
        return 'Ca';
    }

    public function getUserCertList($userId)
    {
        $i = new IndexParser(dirname(__DIR__).'/data/index.txt');

        return $i->getUserCertList($userId);
    }

    public function hasCert($commonName)
    {
        return $this->getCertInfo($commonName) ? true : false;
    }

    public function getCertInfo($commonName)
    {
        $i = new IndexParser(dirname(__DIR__).'/data/index.txt');

        return $i->getCertInfo($commonName);
    }

    public function getCertList()
    {
        $i = new IndexParser(dirname(__DIR__).'/data/index.txt');

        return $i->getCertList();
    }

    public function getCrl()
    {
        return 'Crl';
    }

    public function getCrlLastModifiedTime()
    {
        return 1234567890;
    }

    public function revokeClientCert($commonName)
    {
    }

    public function initCa(array $caConfig)
    {
        // NOP
    }
}
