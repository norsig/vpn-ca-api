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

    public function getConfig($configTemplate = 'client.twig')
    {
        // configTemplateDir is where templates are placed to override the
        // default template
        $configTemplateDir = dirname(dirname(dirname(__DIR__))).'/config/views';
        $defaultTemplateDir = dirname(dirname(dirname(__DIR__))).'/views';

        $templateDirs = array();

        // the template directory actually needs to exist, otherwise the
        // Twig_Loader_Filesystem class will throw an exception when loading
        // templates, the actual template does not need to exist though...
        if (false !== is_dir($configTemplateDir)) {
            $templateDirs[] = $configTemplateDir;
        }
        $templateDirs[] = $defaultTemplateDir;

        $loader = new Twig_Loader_Filesystem($templateDirs);
        $twig = new Twig_Environment($loader);

        return $twig->render(
            $configTemplate,
            $this->configData
        );
    }
}
