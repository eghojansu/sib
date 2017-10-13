<?php

use Eghojansu\Bundle\SetupBundle\Service\Setup;

$ups = [0, 1, 5, 6];
$autoloadFile = null;
$notFound = true;
foreach ($ups as $up) {
    $autoloadFile = __DIR__ . str_repeat('/..', $up) . '/vendor/autoload.php';
    if (file_exists($autoloadFile)) {
        $notFound = false;
        break;
    }
}

if ($notFound) {
    throw new RuntimeException('Install dependencies to run test suite');
}

require_once($autoloadFile);
require_once(__DIR__.'/AppTestKernel.php');

$files = [
    Setup::HISTORY_FILENAME,
    Setup::MAINTENANCE_FILENAME,
    'parameters.yml'
];
foreach ($files as $file) {
    @unlink(__DIR__ . '/var/' . $file);
}

// copy initial parameters
copy(__DIR__ .'/Resources/config/parameters.yml.dist', __DIR__.'/var/parameters.yml');
