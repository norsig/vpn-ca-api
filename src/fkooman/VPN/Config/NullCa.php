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

use RuntimeException;

class NullCa implements CaInterface
{
    public function generateServerCert($commonName, $dhSize)
    {
        return array(
            'cert' => sprintf('ServerCert for %s', $commonName),
            'key' => sprintf('ServerCert for %s', $commonName),
            'dh' => sprintf('ServerDh for %s', $commonName),
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

    public function hasCert($commonName)
    {
        if ('foo' === $commonName) {
            return true;
        }
        if ('foo_foo' === $commonName) {
            return true;
        }
        if ('foo_bar' === $commonName) {
            return true;
        }
        if ('already_revoked' === $commonName) {
            return true;
        }

        return false;
    }

    public function getCaCert()
    {
        return 'Ca';
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
        if ('already_revoked' === $commonName) {
            throw new RuntimeException('already revoked');
        }
    }

    public function initCa(array $caConfig)
    {
        // NOP
    }
}
