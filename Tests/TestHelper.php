<?php

use Eghojansu\Bundle\SetupBundle\Service\Setup;

class TestHelper
{
    const FILE_BY_LISTENER = 'created_by_setup_listener.txt';
    const FILE_BY_SETUP = 'test_yaml.yml';
    const FILE_PARAMETERS = 'parameters.yml';

    public static function prepare()
    {
        $files = [
            Setup::HISTORY_FILENAME,
            Setup::MAINTENANCE_FILENAME,
            self::FILE_BY_LISTENER,
            self::FILE_BY_SETUP,
            self::FILE_PARAMETERS,
        ];
        foreach ($files as $file) {
            @unlink(__DIR__ . '/var/' . $file);
        }

        // copy initial parameters
        copy(__DIR__ .'/Resources/config/parameters.yml.dist', self::varfilepath(self::FILE_PARAMETERS));
    }

    public static function varfilepath($file)
    {
        return __DIR__.'/var/' . $file;
    }
}
