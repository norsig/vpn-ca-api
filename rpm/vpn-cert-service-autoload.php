<?php
$vendorDir = '/usr/share/php';
$baseDir   = dirname(__DIR__);

require_once $vendorDir.'/Symfony/Component/ClassLoader/UniversalClassLoader.php';

use Symfony\Component\ClassLoader\UniversalClassLoader;

$loader = new UniversalClassLoader();
$loader->registerNamespaces(array(
    'fkooman\\VPN'             => $baseDir.'/src',
    'fkooman\\Rest'            => $vendorDir,
    'fkooman\\Json'            => $vendorDir,
    'fkooman\\Http'            => $vendorDir,
    'fkooman\\Config'          => $vendorDir,
    'Symfony\\Component\\Yaml' => $vendorDir,
));

$loader->register();
