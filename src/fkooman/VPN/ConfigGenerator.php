<?php

namespace fkooman\VPN;

use Twig_Loader_Filesystem;
use Twig_Environment;

class ConfigGenerator
{
    /** @var fkooman\VPN\EasyRsa */
    private $easyRsa;

    /** @var array */
    private $remotes;

    public function __construct(EasyRsa $easyRsa, array $remotes)
    {
        $this->easyRsa = $easyRsa;
        $this->remotes = $remotes;
    }

    public function generateClientConfig($commonName)
    {
        $certKey = $this->easyRsa->generateClientCert($commonName);

        $loader = new Twig_Loader_Filesystem(
            dirname(dirname(dirname(__DIR__)))."/views"
        );
        $twig = new Twig_Environment($loader);
        $output = $twig->render(
            "client.twig",
            array(
                "cn" => $commonName,
                "remotes" => $this->remotes,
                "ca" => $this->easyRsa->getCaCert(),
                "cert" => $certKey['cert'],
                "key" => $certKey['key'],
            )
        );

        return $output;
    }
}
