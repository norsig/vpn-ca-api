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
use DateTime;
use DateTimeZone;

/**
 * OpenSSL index.txt parser.
 *
 * @see http://pki-tutorial.readthedocs.org/en/latest/cadb.html
 */
class IndexParser
{
    /** @var string */
    private $indexFile;

    public function __construct($indexFile)
    {
        $this->indexFile = $indexFile;
    }

    public function getCertList()
    {
        return $this->parse();
    }

    public function getUserCertList($userId)
    {
        return $this->parse($userId);
    }

    public function getCertInfo($commonName)
    {
        return $this->parse(null, $commonName);
    }

    private function parse($inUserId = null, $inCommonName = null)
    {
        $certTable = array();
        $handle = @fopen($this->indexFile, 'r');

        if (!$handle) {
            throw new RuntimeException('unable to open CA DB file');
        }

        while (false !== $buffer = fgetcsv($handle, 4096, "\t")) {
            if (6 !== count($buffer)) {
                throw new RuntimeException('CA DB parse error');
            }

            $commonName = $this->extractCommonName($buffer[5]);
            if (false === $commonName) {
                throw new RuntimeException('unable to extract CN');
            }

            if (false === strpos($commonName, '_')) {
                // probably a server, they do not have _s
                continue;
            }

            if (null !== $inCommonName) {
                // we only want a particular commonName
                if ($commonName !== $inCommonName) {
                    continue;
                }
            }

            $userName = explode('_', $commonName)[0];
            if (!is_null($inUserId) && $userName !== $inUserId) {
                continue;
            }

            $configName = explode('_', $commonName, 2)[1];

            $dateTimeZone = new DateTimeZone('UTC');

            $expDateTime = DateTime::createFromFormat('ymdHis?', $buffer[1], $dateTimeZone);
            $expDateTimeStamp = $expDateTime->getTimeStamp();
            if (!empty($buffer[2])) {
                $revDateTime = DateTime::createFromFormat('ymdHis?', $buffer[2], $dateTimeZone);
                $revDateTimeStamp = $revDateTime->getTimeStamp();
            } else {
                $revDateTimeStamp = false;
            }

            $certTable[] = array(
                'user_id' => $userName,
                'name' => $configName,
                'state' => $buffer[0],  // R(revoked), V(alid), E(xpired)
                'exp' => $expDateTimeStamp,
                'rev' => $revDateTimeStamp,
            );
        }

        if (!@feof($handle)) {
            throw new RuntimeException('unexpected EOF');
        }
        @fclose($handle);

        if (!is_null($inCommonName)) {
            if (1 !== count($certTable)) {
                return false;
            }

            return $certTable[0];
        }

        return $certTable;
    }

    private function extractCommonName($dnString)
    {
        if (0 !== strpos($dnString, '/')) {
            // XXX: invalid DN???
            return false;
        }

        $dnFields = explode('/', substr($dnString, 1));
        foreach ($dnFields as $dnField) {
            if (0 === strpos($dnField, 'CN')) {
                // found CN
                return explode('=', $dnField)[1];
            }
        }

        // XXX: no CN???
        return false;
    }
}
