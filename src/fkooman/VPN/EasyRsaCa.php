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

use RuntimeException;

class EasyRsaCa implements CaInterface
{
    /** @var string */
    private $easyRsaTargetPath;

    /** @var string */
    private $easyRsaSourcePath;

    /** @var string */
    private $openVpnPath;

    public function __construct($easyRsaTargetPath)
    {
        $this->easyRsaTargetPath = $easyRsaTargetPath;
        $this->easyRsaSourcePath = '/usr/share/easy-rsa/2.0';
        $this->openVpnPath = '/usr/sbin/openvpn';
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
            $this->easyRsaTargetPath,
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
            $this->easyRsaTargetPath
        );

        return trim(file_get_contents($taFile));
    }

    private function generateTlsAuthKey()
    {
        $taFile = sprintf(
            '%s/keys/ta.key',
            $this->easyRsaTargetPath
        );

        $this->execute(
            sprintf(
                '%s --genkey --secret %s',
                $this->openVpnPath,
                $taFile
            )
        );
    }

    private function generateCert($commonName, $isServer = false)
    {
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
        $certFile = sprintf('%s/keys/%s.crt', $this->easyRsaTargetPath, $commonName);

        return file_exists($certFile);
    }

    public function getCaCert()
    {
        return $this->getCertFile('ca.crt');
    }

    public function getCrl()
    {
        $crlFile = sprintf(
            '%s/keys/%s',
            $this->easyRsaTargetPath,
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
            $this->easyRsaTargetPath,
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
            $this->easyRsaTargetPath,
            'crl.pem'
        );

        if (!file_exists($crlFile)) {
            return;
        }

        return filesize($crlFile);
    }

    public function revokeClientCert($commonName)
    {
        $this->execute(sprintf('revoke-full %s', $commonName));
    }

    private function getCertFile($certFile)
    {
        $certFile = sprintf(
            '%s/keys/%s',
            $this->easyRsaTargetPath,
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
            $this->easyRsaTargetPath,
            $keyFile
        );

        return trim(file_get_contents($keyFile));
    }

    public function initCa(array $caConfig)
    {
        echo $this->easyRsaTargetPath;

        if (!file_exists($this->easyRsaTargetPath)) {
            if (false === @mkdir($this->easyRsaTargetPath, 0700, true)) {
                throw new RuntimeException('folder "%s" could not be created', $this->easyRsaTargetPath);
            }
        }
        if (!file_exists($this->easyRsaSourcePath)) {
            throw new RuntimeException(sprintf('folder "%s" does not exist', $this->easyRsaSourcePath));
        }
        foreach (glob($this->easyRsaSourcePath.'/*') as $file) {
            $fp = fileperms($file);
            copy($file, $this->easyRsaTargetPath.'/'.basename($file));
            // also keep file permissions
            chmod($this->easyRsaTargetPath.'/'.basename($file), $fp);
        }

        # update the 'vars' file
        $search = array(
            'export KEY_SIZE=2048',
            'export CA_EXPIRE=3650',
            'export KEY_EXPIRE=3650',
            'export KEY_COUNTRY="US"',
            'export KEY_PROVINCE="CA"',
            'export KEY_CITY="SanFrancisco"',
            'export KEY_ORG="Fort-Funston"',
            'export KEY_EMAIL="me@myhost.mydomain"',
            'export KEY_OU="MyOrganizationalUnit"',
        );
        $replace = array(
            sprintf('export KEY_SIZE=%d', $caConfig['key_size']),
            sprintf('export CA_EXPIRE=%d', $caConfig['ca_expire']),
            sprintf('export KEY_EXPIRE=%d', $caConfig['key_expire']),
            sprintf('export KEY_COUNTRY="%s"', $caConfig['key_country']),
            sprintf('export KEY_PROVINCE="%s"', $caConfig['key_province']),
            sprintf('export KEY_CITY="%s"', $caConfig['key_city']),
            sprintf('export KEY_ORG="%s"', $caConfig['key_org']),
            sprintf('export KEY_EMAIL="%s"', $caConfig['key_email']),
            sprintf('export KEY_OU="%s"', $caConfig['key_ou']),
        );
        $varsFile = $this->easyRsaTargetPath.'/vars';
        $varsContent = str_replace($search, $replace, file_get_contents($varsFile));
        file_put_contents($varsFile, $varsContent);

        $this->execute('clean-all');
        $this->execute('pkitool --initca');
        $this->generateTlsAuthKey();
    }

    private function execute($command, $isQuiet = true)
    {
        // if not absolute path, prepend with './'
        $command = 0 !== strpos($command, '/') ? sprintf('./%s', $command) : $command;

        // by default we are quiet
        $quietSuffix = $isQuiet ? ' >/dev/null 2>/dev/null' : '';

        $cmd = sprintf(
            'cd %s && source ./vars >/dev/null 2>/dev/null && %s %s',
            $this->easyRsaTargetPath,
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
