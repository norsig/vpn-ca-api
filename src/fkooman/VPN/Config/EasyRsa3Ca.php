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

class EasyRsa3Ca implements CaInterface
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
            $this->config['sourcePath'] = '/usr/share/easy-rsa/3.0.0';
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

    public function initCa(array $caConfig)
    {
        if (!file_exists($this->config['sourcePath']) || !is_dir($this->config['sourcePath'])) {
            throw new RuntimeException(sprintf('folder "%s" does not exist', $this->config['sourcePath']));
        }

        self::copyDir($this->config['sourcePath'], $this->config['targetPath']);

        $config = array(
            sprintf('set_var EASYRSA_KEY_SIZE %d', $caConfig['key_size']),
            sprintf('set_var EASYRSA_CA_EXPIRE %d', $caConfig['ca_expire']),
            sprintf('set_var EASYRSA_CERT_EXPIRE %d', $caConfig['cert_expire']),
            sprintf('set_var EASYRSA_REQ_CN	"%s"', $caConfig['ca_cn']),
            sprintf('set_var EASYRSA_BATCH "1"'),
        );
        $varsTargetFile = $this->config['targetPath'].'/vars';
        if (false === @file_put_contents($varsTargetFile, implode("\n", $config)."\n")) {
            throw new RuntimeException('unable to write "vars" file');
        }

        $this->execute('easyrsa init-pki');
        $this->execute('easyrsa build-ca nopass');
        $this->execute('easyrsa gen-crl');
        $this->generateTlsAuthKey();
    }

    private function generateTlsAuthKey()
    {
        $taFile = sprintf(
            '%s/pki/ta.key',
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

    public function generateServerCert($commonName, $dhSize)
    {
        // we ignore $dhSize here, only for EasyRSA 2
        $certKeyDh = $this->generateCert($commonName, true);
        $certKeyDh['dh'] = $this->generateDh();

        return $certKeyDh;
    }

    private function generateDh()
    {
        $this->execute('easyrsa gen-dh');

        $dhFile = sprintf(
            '%s/pki/dh.pem',
            $this->config['targetPath']
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
            '%s/pki/ta.key',
            $this->config['targetPath']
        );

        return trim(file_get_contents($taFile));
    }

    private function generateCert($commonName, $isServer = false)
    {
        if ($isServer) {
            $this->execute(sprintf('easyrsa build-server-full %s nopass', $commonName));
        } else {
            $this->execute(sprintf('easyrsa build-client-full %s nopass', $commonName));
        }

        return array(
            'cert' => $this->getCertFile(sprintf('%s.crt', $commonName)),
            'key' => $this->getKeyFile(sprintf('%s.key', $commonName)),
        );
    }

    public function hasCert($commonName)
    {
        $certFile = sprintf('%s/pki/issued/%s.crt', $this->config['targetPath'], $commonName);

        return file_exists($certFile);
    }

    public function getCaCert()
    {
        return $this->getCertFile('ca.crt');
    }

    public function getCrl()
    {
        $crlFile = sprintf(
            '%s/pki/crl.pem',
            $this->config['targetPath']
        );

        return file_get_contents($crlFile);
    }

    public function getCrlLastModifiedTime()
    {
        $crlFile = sprintf(
            '%s/pki/crl.pem',
            $this->config['targetPath']
        );

        return gmdate('r', filemtime($crlFile));
    }

    public function getCrlFileSize()
    {
        $crlFile = sprintf(
            '%s/pki/crl.pem',
            $this->config['targetPath']
        );

        return filesize($crlFile);
    }

    public function revokeClientCert($commonName)
    {
        $this->execute(sprintf('easyrsa revoke %s', $commonName));
        $this->execute('easyrsa gen-crl');
    }

    private function getCertFile($certFile)
    {
        if ('ca.crt' === $certFile) {
            $certFile = sprintf('%s/pki/ca.crt', $this->config['targetPath']);
        } else {
            $certFile = sprintf(
                '%s/pki/issued/%s',
                $this->config['targetPath'],
                $certFile
            );
        }

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
            '%s/pki/private/%s',
            $this->config['targetPath'],
            $keyFile
        );

        return trim(file_get_contents($keyFile));
    }

    private function execute($command, $isQuiet = true)
    {
        // if not absolute path, prepend with './'
        $command = 0 !== strpos($command, '/') ? sprintf('./%s', $command) : $command;

        // by default we are quiet
        $quietSuffix = $isQuiet ? ' >/dev/null 2>/dev/null' : '';

        $cmd = sprintf(
            'cd %s && %s %s',
            $this->config['targetPath'],
            $command,
            $quietSuffix
        );
        $output = array();
        $returnValue = 0;
        // XXX: check return value, log output?
        exec($cmd, $output, $returnValue);

        return $output;
    }

    private static function copyDir($source, $target)
    {
        foreach (glob($source.'/*') as $file) {
            if (is_file($file)) {
                $fp = fileperms($file);
                copy($file, $target.'/'.basename($file));
                chmod($target.'/'.basename($file), $fp);
            }
            if (is_dir($file)) {
                @mkdir($target.'/'.basename($file));
                self::copyDir($file, $target.'/'.basename($file));
            }
        }
    }
}
