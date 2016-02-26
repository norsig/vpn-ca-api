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

use RuntimeException;

class EasyRsa3Ca implements CaInterface
{
    /** @var array */
    private $config;

    public function __construct(array $config)
    {
        $this->config = array();

        // where easy-rsa (3) is installed
        if (!array_key_exists('easyRsaPath', $config)) {
            $this->config['easyRsaPath'] = '/usr/share/easy-rsa/3.0';
        } else {
            $this->config['easyRsaPath'] = $config['easyRsaPath'];
        }

        // where the CA data is stored
        if (!array_key_exists('caPath', $config)) {
            $this->config['caPath'] = sprintf('%s/data/easy-rsa', dirname(dirname(dirname(dirname(__DIR__)))));
        } else {
            $this->config['caPath'] = $config['caPath'];
        }

        if (!array_key_exists('openVpnPath', $config)) {
            $this->config['openVpnPath'] = '/usr/sbin/openvpn';
        } else {
            $this->config['openVpnPath'] = $config['openVpnPath'];
        }

        // create target directory if it does not exist   
        if (!file_exists($this->config['caPath'])) {
            if (false === @mkdir($this->config['caPath'], 0700, true)) {
                throw new RuntimeException(
                    sprintf(
                        'folder "%s" could not be created',
                        $this->config['caPath']
                    )
                );
            }
        }
    }

    public function initCa(array $caConfig)
    {
        if (!file_exists($this->config['easyRsaPath']) || !is_dir($this->config['easyRsaPath'])) {
            throw new RuntimeException(sprintf('folder "%s" does not exist', $this->config['easyRsaPath']));
        }

        $config = array(
            sprintf('set_var EASYRSA %s', $this->config['easyRsaPath']),
            sprintf('set_var EASYRSA_PKI %s/pki', $this->config['caPath']),
            sprintf('set_var EASYRSA_KEY_SIZE %d', $caConfig['key_size']),
            sprintf('set_var EASYRSA_CA_EXPIRE %d', $caConfig['ca_expire']),
            sprintf('set_var EASYRSA_CERT_EXPIRE %d', $caConfig['cert_expire']),
            sprintf('set_var EASYRSA_REQ_CN	"%s"', $caConfig['ca_cn']),
            sprintf('set_var EASYRSA_BATCH "1"'),
        );
        $varsTargetFile = $this->config['caPath'].'/vars';
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
            $this->config['caPath']
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
            $this->config['caPath']
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
            '%s/pki/ta.key',
            $this->config['caPath']
        );

        return trim(Utils::getFile($taFile));
    }

    private function generateCert($commonName, $isServer = false)
    {
        if ($isServer) {
            $this->execEasyRsa(sprintf('build-server-full %s nopass', $commonName));
        } else {
            $this->execEasyRsa(sprintf('build-client-full %s nopass', $commonName));
        }

        $certFile = $this->getCertFile(sprintf('%s.crt', $commonName));
        $keyFile = $this->getKeyFile(sprintf('%s.key', $commonName));
        $parsedCert = openssl_x509_parse($certFile);

        return array(
            'cert' => $certFile,
            'key' => $keyFile,
            'valid_from' => $parsedCert['validFrom_time_t'],
            'valid_to' => $parsedCert['validTo_time_t'],
        );
    }

    public function getCaCert()
    {
        return $this->getCertFile('ca.crt');
    }

    public function getCrl()
    {
        $crlFile = sprintf(
            '%s/pki/crl.pem',
            $this->config['caPath']
        );

        return Utils::getFile($crlFile);
    }

    public function getCrlLastModifiedTime()
    {
        $crlFile = sprintf(
            '%s/pki/crl.pem',
            $this->config['caPath']
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
        $this->execEasyRsa(
            sprintf('revoke %s', $commonName)
        );
        $this->execEasyRsa('gen-crl');
    }

    public function getCertList()
    {
        $indexFile = sprintf(
            '%s/pki/index.txt',
            $this->config['caPath']
        );

        $indexParser = new IndexParser($indexFile);

        return $indexParser->getCertList();
    }

    public function getUserCertList($userId)
    {
        $indexFile = sprintf(
            '%s/pki/index.txt',
            $this->config['caPath']
        );

        $indexParser = new IndexParser($indexFile);

        return $indexParser->getUserCertList($userId);
    }

    public function getCertInfo($commonName)
    {
        $indexFile = sprintf(
            '%s/pki/index.txt',
            $this->config['caPath']
        );

        $indexParser = new IndexParser($indexFile);

        return $indexParser->getCertInfo($commonName);
    }

    private function getCertFile($certFile)
    {
        if ('ca.crt' === $certFile) {
            $certFile = sprintf('%s/pki/ca.crt', $this->config['caPath']);
        } else {
            $certFile = sprintf(
                '%s/pki/issued/%s',
                $this->config['caPath'],
                $certFile
            );
        }

        // only return the certificate, strip junk before and after the actual
        // certificate
        $pattern = '/(-----BEGIN CERTIFICATE-----.*-----END CERTIFICATE-----)/msU';
        if (1 === preg_match($pattern, Utils::getFile($certFile), $matches)) {
            return $matches[1];
        }

        return false;
    }

    private function getKeyFile($keyFile)
    {
        $keyFile = sprintf(
            '%s/pki/private/%s',
            $this->config['caPath'],
            $keyFile
        );

        return trim(Utils::getFile($keyFile));
    }

    private function execOpenVpn($args)
    {
        $cmd = sprintf('%s %s >/dev/null 2>/dev/null', $this->config['openVpnPath'], $args);
        Utils::exec($cmd);
    }

    private function execEasyRsa($args)
    {
        $cmd = sprintf('%s/easyrsa --vars=%s/vars %s >/dev/null 2>/dev/null', $this->config['easyRsaPath'], $this->config['caPath'], $args);
        Utils::exec($cmd);
    }
}
