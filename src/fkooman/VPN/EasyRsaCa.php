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
namespace fkooman\VPN;

class EasyRsaCa implements CaInterface
{
    /** @var string */
    private $easyRsaPath;

    /** @var fkooman\VPN\PdoStorage */
    private $db;

    /** @var string */
    private $keySize;

    /** @var string */
    private $pathToOpenVpn;

    public function __construct($easyRsaPath, PdoStorage $db, $keySize, $pathToOpenVpn = '/usr/sbin/openvpn')
    {
        $this->easyRsaPath = $easyRsaPath;
        $this->db = $db;
        $this->keySize = $keySize;
        $this->pathToOpenVpn = $pathToOpenVpn;
    }

    public function generateServerCert($commonName)
    {
        $certKeyDh = $this->generateCert($commonName, true);
        $certKeyDh['dh'] = $this->generateDh();

        return $certKeyDh;
    }

    public function generateDh()
    {
        $this->execute('./build-dh');

        $dhFile = sprintf(
            '%s/keys/dh%s.pem',
            $this->easyRsaPath,
            $this->keySize
        );

        return trim(file_get_contents($dhFile));
    }

    public function generateClientCert($commonName)
    {
        return $this->generateCert($commonName, false);
    }

    public function getTlsAuthKey()
    {
        $taFile = sprintf(
            '%s/keys/ta.key',
            $this->easyRsaPath
        );

        return trim(file_get_contents($taFile));
    }

    private function generateTlsAuthKey()
    {
        $taFile = sprintf(
            '%s/keys/ta.key',
            $this->easyRsaPath
        );

        $this->execute(
            sprintf(
                '%s --genkey --secret %s',
                $this->pathToOpenVpn,
                $taFile
            )
        );
    }

    private function generateCert($commonName, $isServer = false)
    {
        $this->db->addCert($commonName);

        if ($isServer) {
            $this->execute(sprintf('pkitool --server %s', $commonName));
        } else {
            $this->execute(sprintf('pkitool %s', $commonName));
        }

        return array(
            'cert' => $this->getCertFile(sprintf('%s.crt', $commonName)),
            'key' => $this->getKeyFile(sprintf('%s.key', $commonName)),
        );
    }

    public function hasCert($commonName)
    {
        return null !== $this->db->getCert($commonName);
    }

    public function getCaCert()
    {
        return $this->getCertFile('ca.crt');
    }

    public function getCrl()
    {
        $crlFile = sprintf(
            '%s/keys/%s',
            $this->easyRsaPath,
            'crl.pem'
        );

        if (!file_exists($crlFile)) {
            return;
        }

        return file_get_contents($crlFile);
    }

    public function getCrlLastModifiedTime()
    {
        $crlFile = sprintf(
            '%s/keys/%s',
            $this->easyRsaPath,
            'crl.pem'
        );

        if (!file_exists($crlFile)) {
            return;
        }

        return gmdate('r', filemtime($crlFile));
    }

    public function getCrlFileSize()
    {
        $crlFile = sprintf(
            '%s/keys/%s',
            $this->easyRsaPath,
            'crl.pem'
        );

        if (!file_exists($crlFile)) {
            return;
        }

        return filesize($crlFile);
    }

    public function revokeClientCert($commonName)
    {
        $this->db->deleteCert($commonName);
        $this->execute(sprintf('revoke-full %s', $commonName));
    }

    private function getCertFile($certFile)
    {
        $certFile = sprintf(
            '%s/keys/%s',
            $this->easyRsaPath,
            $certFile
        );
        // only return the certificate, strip junk before and after the actual
        // certificate

        $pattern = '/(-----BEGIN CERTIFICATE-----.*-----END CERTIFICATE-----)/msU';
        if (1 === preg_match($pattern, file_get_contents($certFile), $matches)) {
            return $matches[1];
        }

        return;
    }

    private function getKeyFile($keyFile)
    {
        $keyFile = sprintf(
            '%s/keys/%s',
            $this->easyRsaPath,
            $keyFile
        );

        return trim(file_get_contents($keyFile));
    }

    public function initCa()
    {
        $this->execute('clean-all');
        $this->execute('pkitool --initca');
        $this->generateTlsAuthKey();
        $this->db->initDatabase();
    }

    private function execute($command, $isQuiet = true)
    {
        // if not absolute path, prepend with './'
        $command = 0 !== strpos($command, '/') ? sprintf('./%s', $command) : $command;

        // by default we are quiet
        $quietSuffix = $isQuiet ? ' >/dev/null 2>/dev/null' : '';

        $cmd = sprintf(
            'cd %s && source ./vars >/dev/null 2>/dev/null && %s %s',
            $this->easyRsaPath,
            $command,
            $quietSuffix
        );
        $output = array();
        $returnValue = 0;
        // FIXME: check return value, log output?
        exec($cmd, $output, $returnValue);

        return $output;
    }
}
