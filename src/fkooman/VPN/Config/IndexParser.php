<?php

namespace fkooman\VPN\Config;

use RuntimeException;
use DateTime;

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

    public function getCertList($userId = null)
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

            $userName = explode('_', $commonName)[0];
            if (!is_null($userId) && $userName !== $userId) {
                continue;
            }

            $configName = explode('_', $commonName, 2)[1];

            $expDateTime = DateTime::createFromFormat('ymdHis?', $buffer[1]);
            $expDateTimeStamp = $expDateTime->getTimeStamp();
            if (!empty($buffer[2])) {
                $revDateTime = DateTime::createFromFormat('ymdHis?', $buffer[2]);
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
