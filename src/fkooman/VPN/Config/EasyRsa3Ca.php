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

        if (!array_key_exists('sourcePath', $config)) {
            $this->config['sourcePath'] = '/usr/share/easy-rsa/3.0.0';
        } else {
            $this->config['sourcePath'] = $config['sourcePath'];
        }

        if (!array_key_exists('targetPath', $config)) {
            $this->config['targetPath'] = sprintf('%s/data/easy-rsa', dirname(dirname(dirname(dirname(__DIR__)))));
        } else {
            $this->config['targetPath'] = $config['targetPath'];
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

        $config = array(
            sprintf('set_var EASYRSA %s', $this->config['sourcePath']),
            sprintf('set_var EASYRSA_PKI %s/pki', $this->config['targetPath']),
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

        $this->execEasyRsa('init-pki');
        $this->execEasyRsa('build-ca nopass');
        $this->execEasyRsa('gen-crl');
        $this->generateTlsAuthKey();
    }

    private function generateTlsAuthKey()
    {
        $taFile = sprintf(
            '%s/pki/ta.key',
            $this->config['targetPath']
        );

        $this->execOpenVpn(
            sprintf('--genkey --secret %s', $taFile)
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
        $this->execEasyRsa('gen-dh');

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
            $this->execEasyRsa(sprintf('build-server-full %s nopass', $commonName));
        } else {
            $this->execEasyRsa(sprintf('build-client-full %s nopass', $commonName));
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
        $this->execEasyRsa(sprintf('revoke %s', $commonName));
        $this->execEasyRsa('gen-crl');
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

    private function execOpenVpn($args)
    {
        $cmd = sprintf('%s %s >/dev/null 2>/dev/null', $this->config['openVpnPath'], $args);
        exec($cmd, $output);

        return $output;
    }

    private function execEasyRsa($args)
    {
        $cmd = sprintf('%s/easyrsa --vars=%s/vars %s >/dev/null 2>/dev/null', $this->config['sourcePath'], $this->config['targetPath'], $args);
        exec($cmd, $output);

        return $output;
    }
}
