<?php

namespace fkooman\VPN;

class EasyRsa
{
    /** @var string */
    private $easyRsaPath;

    /** @var fkooman\VPN\PdoStorage */
    private $db;

    /** @var string */
    private $openSslPath;

    public function __construct($easyRsaPath, PdoStorage $db, $openSslPath = "/usr/bin/openssl")
    {
        $this->easyRsaPath = $easyRsaPath;
        $this->db = $db;
        $this->openSslPath = $openSslPath;
    }

    public function generateServerCert($commonName)
    {
        return $this->generateCert($commonName, true);
    }

    public function generateClientCert($commonName)
    {
        return $this->generateCert($commonName, false);
    }

    public function generateCert($commonName, $isServer = false)
    {
        $this->validateCommonName($commonName);

        if ($this->hasCert($commonName)) {
            throw new EasyRsaException("cert for this common name already exists");
        }

        $this->db->addCert($commonName);

        if ($isServer) {
            $this->execute(sprintf("pkitool --server %s", $commonName));
        } else {
            $this->execute(sprintf("pkitool %s", $commonName));
        }

        return array(
            "cert" => $this->getCertFile(sprintf("%s.crt", $commonName)),
            "key" => $this->getKeyFile(sprintf("%s.key", $commonName)),
        );
    }

    public function hasCert($commonName)
    {
        $this->validateCommonName($commonName);

        return null !== $this->db->getCert($commonName);
    }

    public function getCaCert()
    {
        return $this->getCertFile("ca.crt");
    }

    public function revokeClientCert($commonName)
    {
        $this->validateCommonName($commonName);
        if (!$this->hasCert($commonName)) {
            throw new EasyRsaException("cert with this common name does not exist");
        }
        $this->db->deleteCert($commonName);
        $this->execute(sprintf("revoke-full %s", $commonName));
    }

    private function getCertFile($certFile)
    {
        $certFile = sprintf(
            "%s/keys/%s",
            $this->easyRsaPath,
            $certFile
        );
        $command = sprintf(
            "%s x509 -inform PEM -in %s",
            $this->openSslPath,
            $certFile
        );

        return implode("\n", $this->execute($command, false));
    }

    private function getKeyFile($keyFile)
    {
        $keyFile = sprintf(
            "%s/keys/%s",
            $this->easyRsaPath,
            $keyFile
        );

        return trim(file_get_contents($keyFile));
    }

    public function initCa()
    {
        $this->execute("clean-all");
        $this->execute("pkitool --initca");
        $this->db->initDatabase();
    }

    private function validateCommonName($commonName)
    {
        if (0 === preg_match('/^[a-zA-Z0-9-_.@]+$/', $commonName)) {
            throw new EasyRsaException("invalid common name");
        }
    }

    private function execute($command, $isQuiet = true)
    {
        // if not absolute path, prepend with "./"
        $command = 0 !== strpos($command, "/") ? sprintf("./%s", $command) : $command;

        // by default we are quiet
        $quietSuffix = $isQuiet ? " >/dev/null 2>/dev/null" : "";

        $cmd = sprintf(
            "cd %s && source ./vars >/dev/null 2>/dev/null && %s %s",
            $this->easyRsaPath,
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
