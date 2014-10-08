<?php

namespace fkooman\VPN;

use Twig_Loader_Filesystem;
use Twig_Environment;

class ConfigGenerator
{
    /** @var array */
    private $configData;

    public function __construct(array $configData)
    {
        $this->configData = $configData;
    }

    public function getConfig($configTemplate = "client.twig")
    {
        $loader = new Twig_Loader_Filesystem(
            dirname(dirname(dirname(__DIR__)))."/views"
        );
        $twig = new Twig_Environment($loader);

        return $twig->render(
            $configTemplate,
            $this->configData
        );
    }
}
