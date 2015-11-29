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

interface CaInterface
{
    /**
     * Generate a certificate for the VPN server.
     * 
     * @param string $commonName
     *
     * @return array the certificate and key in array with keys 'cert' and
     *               'key'
     */
    public function generateServerCert($commonName);

    public function generateDh();

    /**
     * Generate a certificate for a VPN client.
     * 
     * @param string $commonName
     *
     * @return array the certificate and key in array with keys 'cert' and
     *               'key'
     */
    public function generateClientCert($commonName);

    public function getTlsAuthKey();

    /** 
     * Check if the CA already issued a certificate.
     *
     * @param string $commonName
     *
     * @return bool whether or not the commonName already has a certificate
     */
    public function hasCert($commonName);

    /**
     * Get the CA root certificate.
     */
    public function getCaCert();

    /**
     * Get the CA CRL.
     */
    public function getCrl();

    /**
     * Get the CA CRL last modified time.
     *
     * @return string the date as RFC 2822 string (GMT)
     */
    public function getCrlLastModifiedTime();

    /**
     * Get the file size of the CA CRL.
     */
    public function getCrlFileSize();

    /**
     * Revoke a VPN client certificate.
     */
    public function revokeClientCert($commonName);

    /**
     * Initialize the CA.
     *
     * @param array $caConfig the CA configuration
     */
    public function initCa(array $caConfig);
}
