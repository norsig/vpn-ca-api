<?php

namespace fkooman\VPN;

class EasyRsa
{
    /** @var string */
    private $easyRsaPath;

    /** @var string */
    private $openSslPath;

    public function __construct($easyRsaPath, $openSslPath = "/usr/bin/openssl")
    {
        $this->easyRsaPath = $easyRsaPath;
        $this->openSslPath = $openSslPath;
    }

    public function cleanAll()
    {
        $this->execute("clean-all");
    }

    public function initCa()
    {
        $this->execute("pkitool --initca");
    }

    public function generateServerCert($commonName)
    {
        $this->execute(sprintf("pkitool --server %s", $commonName));
    }

    public function generateClientCert($commonName)
    {
        $this->execute(sprintf("pkitool %s", $commonName));

        return array(
            "cert" => $this->getCertFile(sprintf("%s.crt", $commonName)),
            "key" => $this->getKeyFile(sprintf("%s.key", $commonName)),
        );
    }

    public function getCaCert()
    {
        return $this->getCertFile("ca.crt");
    }

    public function revokeClientCert($commonName)
    {
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

    public function execute($command, $isQuiet = true)
    {
        // if not absolute path, prepend with "./"
        $command = 0 !== strpos($command, "/") ? sprintf("./%s", $command) : $command;

        // by default we are quiet
        $quiet = $isQuiet ? " >/dev/null 2>/dev/null" : "";

        $cmd = sprintf(
            "cd %s && source ./vars >/dev/null 2>/dev/null && %s %s",
            $this->easyRsaPath,
            $command,
            $quiet
        );
        $output = array();
        $returnValue = 0;
        // FIXME: check return value, log output?
        exec($cmd, $output, $returnValue);

        return $output;
    }
}
