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
namespace fkooman\VPN\CA;

interface CaInterface
{
    /**
     * Generate a certificate for the VPN server.
     * 
     * @param string $commonName
     * @param int    $dhSize     the size of the DH parameter
     *
     * @return array the certificate, key and dh parameters in array with keys 
     *               'cert', 'key', 'dh', 'valid_from' and 'valid_to'
     */
    public function generateServerCert($commonName, $dhSize);

    /**
     * Generate a certificate for a VPN client.
     * 
     * @param string $commonName
     *
     * @return array the certificate and key in array with keys 'cert', 'key',
     *               'valid_from' and 'valid_to'
     */
    public function generateClientCert($commonName);

    /**
     * Get the TLS Auth Key.
     *
     * @return string the TLS Auth Key
     */
    public function getTlsAuthKey();

    /**
     * Get the CA root certificate.
     *
     * @return string the CA certificate in PEM format
     */
    public function getCaCert();

    /**
     * Get the CA CRL.
     *
     * @return string the CRL in PEM format
     */
    public function getCrl();

    /**
     * Get the CA CRL last modified time.
     *
     * @return int unix timestamp of last modified time
     */
    public function getCrlLastModifiedTime();

    /**
     * Revoke a VPN client certificate.
     */
    public function revokeClientCert($commonName);

    /**
     * Get the list of all certificates.
     *
     * @return array list of certificates
     */
    public function getCertList();

    /**
     * Get the list of all certificates for a particular user.
     *
     * @param string $userId the user ID
     *
     * @return array list of certificates for this user
     */
    public function getUserCertList($userId);

    /**
     * Get the information about a certificate for a particular CN.
     *
     * @param string $commonName the CN
     *
     * @return array|false information about the certificate or false if the
     *                     certificate does not exist
     */
    public function getCertInfo($commonName);

    /**
     * Initialize the CA.
     *
     * @param array $caConfig the CA configuration
     */
    public function initCa(array $caConfig);
}
