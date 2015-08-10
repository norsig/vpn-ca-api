<?php

$vendorDir = '/usr/share/php';
$pearDir = '/usr/share/pear';
$baseDir = dirname(__DIR__);

require_once $vendorDir.'/password_compat/password.php';
require_once $vendorDir.'/Symfony/Component/ClassLoader/UniversalClassLoader.php';

use Symfony\Component\ClassLoader\UniversalClassLoader;

$loader = new UniversalClassLoader();
$loader->registerNamespaces(
    array(
        'fkooman\\VPN' => $baseDir.'/src',
        'fkooman\\Rest' => $vendorDir,
        'fkooman\\Json' => $vendorDir,
        'fkooman\\Http' => $vendorDir,
        'fkooman\\Ini' => $vendorDir,
        'fkooman\\Tpl' => $vendorDir,
    )
);
$loader->registerPrefixes(
    array(
        'Twig_' => array($pearDir, $vendorDir),
    )
);

$loader->register();
