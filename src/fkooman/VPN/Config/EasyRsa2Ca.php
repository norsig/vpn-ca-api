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

class EasyRsa2Ca implements CaInterface
{
    /** @var array */
    private $config;

    public function __construct(array $config)
    {
        $this->config = array();
        if (!array_key_exists('targetPath', $config)) {
            $this->config['targetPath'] = sprintf('%s/data/easy-rsa', dirname(dirname(dirname(dirname(__DIR__)))));
        } else {
            $this->config['targetPath'] = $config['targetPath'];
        }

        if (!array_key_exists('sourcePath', $config)) {
            $this->config['sourcePath'] = '/usr/share/easy-rsa/2.0';
        } else {
            $this->config['sourcePath'] = $config['sourcePath'];
        }

        if (!array_key_exists('openVpnPath', $config)) {
            $this->config['openVpnPath'] = '/usr/sbin/openvpn';
        } else {
            $this->config['openVpnPath'] = $config['openVpnPath'];
        }

        // create target directory if it does not exist   
        if (!file_exists($this->config['targetPath'])) {
            if (false === @mkdir($this->config['targetPath'], 0700, true)) {
                throw new RuntimeException(
                    sprintf(
                        'folder "%s" could not be created',
                        $this->config['targetPath']
                    )
                );
            }
        }
    }

    public function generateServerCert($commonName, $dhSize)
    {
        $certKeyDh = $this->generateCert($commonName, true);
        $certKeyDh['dh'] = $this->generateDh($dhSize);

        return $certKeyDh;
    }

    private function generateDh($dhSize)
    {
        $this->execute('./build-dh');

        $dhFile = sprintf(
            '%s/keys/dh%s.pem',
            $this->config['targetPath'],
            $dhSize
        );

        return trim(Utils::getFile($dhFile));
    }

    public function generateClientCert($commonName)
    {
        return $this->generateCert($commonName, false);
    }

    public function getTlsAuthKey()
    {
        $taFile = sprintf(
            '%s/keys/ta.key',
            $this->config['targetPath']
        );

        return trim(Utils::getFile($taFile));
    }

    private function generateTlsAuthKey()
    {
        $taFile = sprintf(
            '%s/keys/ta.key',
            $this->config['targetPath']
        );

        $this->execute(
            sprintf(
                '%s --genkey --secret %s',
                $this->config['openVpnPath'],
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
        $certFile = sprintf('%s/keys/%s.crt', $this->config['targetPath'], $commonName);

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
            $this->config['targetPath'],
            'crl.pem'
        );

        return Utils::getFile($crlFile);
    }

    public function getCrlLastModifiedTime()
    {
        $crlFile = sprintf(
            '%s/keys/%s',
            $this->config['targetPath'],
            'crl.pem'
        );

        $fileTime = @filemtime($crlFile);
        if (false === $fileTime) {
            throw new RuntimeException(
                sprintf('unable to determine file modification time of "%s"', $crlFile)
            );
        }

        return $fileTime;
    }

    public function revokeClientCert($commonName)
    {
        $this->execute(sprintf('revoke-full %s', $commonName));
    }

    private function getCertFile($certFile)
    {
        $certFile = sprintf(
            '%s/keys/%s',
            $this->config['targetPath'],
            $certFile
        );
        // only return the certificate, strip junk before and after the actual
        // certificate

        $pattern = '/(-----BEGIN CERTIFICATE-----.*-----END CERTIFICATE-----)/msU';
        if (1 === preg_match($pattern, Utils::getFile($certFile), $matches)) {
            return $matches[1];
        }

        return;
    }

    private function getKeyFile($keyFile)
    {
        $keyFile = sprintf(
            '%s/keys/%s',
            $this->config['targetPath'],
            $keyFile
        );

        return trim(Utils::getFile($keyFile));
    }

    public function initCa(array $caConfig)
    {
        if (!file_exists($this->config['sourcePath']) || !is_dir($this->config['sourcePath'])) {
            throw new RuntimeException(sprintf('folder "%s" does not exist', $this->config['sourcePath']));
        }
        foreach (glob($this->config['sourcePath'].'/*') as $file) {
            $fp = fileperms($file);
            copy($file, $this->config['targetPath'].'/'.basename($file));
            // also keep file permissions
            chmod($this->config['targetPath'].'/'.basename($file), $fp);
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
        $varsFile = $this->config['targetPath'].'/vars';
        $varsContent = str_replace($search, $replace, Utils::getFile($varsFile));

        if (false === @file_put_contents($varsFile, $varsContent)) {
            throw new RuntimeException('unable to write "vars" file');
        }
        $this->execute('clean-all');
        $this->execute('pkitool --initca');
        $this->generateTlsAuthKey();
        // generate a client config and revoke it to get a CRL
        $this->generateCert('revoke@example.org');
        $this->revokeClientCert('revoke@example.org');
    }

    private function execute($command, $isQuiet = true)
    {
        // if not absolute path, prepend with './'
        $command = 0 !== strpos($command, '/') ? sprintf('./%s', $command) : $command;

        // by default we are quiet
        $quietSuffix = $isQuiet ? ' >/dev/null 2>/dev/null' : '';

        $cmd = sprintf(
            'cd %s && . ./vars >/dev/null 2>/dev/null && %s %s',
            $this->config['targetPath'],
            $command,
            $quietSuffix
        );

        Utils::exec($cmd);
    }
}
