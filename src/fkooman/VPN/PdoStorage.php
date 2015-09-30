<?php

namespace fkooman\VPN;

use PDO;
use RuntimeException;

class PdoStorage
{
    private $db;
    private $prefix;

    public function __construct(PDO $db, $prefix = '')
    {
        $this->db = $db;
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->prefix = $prefix;
    }

    public function getCert($commonName)
    {
        $stmt = $this->db->prepare(
            sprintf(
                'SELECT common_name FROM %s WHERE common_name = :common_name',
                $this->prefix.'certs'
            )
        );
        $stmt->bindValue(':common_name', $commonName, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (false !== $result) {
            return $result;
        }

        return;
    }

    public function addCert($commonName)
    {
        $stmt = $this->db->prepare(
            sprintf(
                'INSERT INTO %s (common_name) VALUES(:common_name)',
                $this->prefix.'certs'
            )
        );
        $stmt->bindValue(':common_name', $commonName, PDO::PARAM_STR);
        $stmt->execute();

        if (1 !== $stmt->rowCount()) {
            throw new RuntimeException('unable to add cert');
        }
    }

    public function deleteCert($commonName)
    {
        $stmt = $this->db->prepare(
            sprintf(
                'DELETE FROM %s WHERE common_name = :common_name',
                $this->prefix.'certs'
            )
        );
        $stmt->bindValue(':common_name', $commonName, PDO::PARAM_STR);
        $stmt->execute();

        if (1 !== $stmt->rowCount()) {
            throw new RuntimeException('unable to delete cert');
        }
    }

    public static function createTableQueries($prefix)
    {
        $query = array();
        $query[] = sprintf(
            'CREATE TABLE IF NOT EXISTS %s (
                common_name VARCHAR(255) NOT NULL,
                UNIQUE (common_name)
            )',
            $prefix.'certs'
        );

        return $query;
    }

    public function initDatabase()
    {
        $queries = self::createTableQueries($this->prefix);
        foreach ($queries as $q) {
            $this->db->query($q);
        }

        $tables = array('certs');
        foreach ($tables as $t) {
            // make sure the tables are empty
            $this->db->query(
                sprintf(
                    'DELETE FROM %s',
                    $this->prefix.$t
                )
            );
        }
    }
}
