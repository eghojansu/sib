<?php

namespace Eghojansu\Bundle\SetupBundle\Service;

use DateTime;
use RuntimeException;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\Request;
use Eghojansu\Bundle\SetupBundle\Utils\ArrayHelper;
use Eghojansu\Bundle\SetupBundle\EghojansuSetupBundle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Setup
{
	const AUTHENTICATED_SESSION_NAME = 'eghojansu_setup_authenticated';
    const HISTORY_INSTALLED_KEY = 'installed';
    const HISTORY_FILENAME = 'setup_history.yml.lock';
    const MAINTENANCE_MAINTENANCE_KEY = 'maintenance';
    const MAINTENANCE_MAINTENANCE_LOG = 'log';
    const MAINTENANCE_FILENAME = 'setup_maintenance.yml.lock';
    const WATERMARK = '# This file was modified by eghojansu_setup';

    /** @var Symfony\Component\HttpFoundation\Session\SessionInterface */
    private $session;

	/** @var Symfony\Component\DependencyInjection\ContainerInterface */
	private $container;

    /** @var array */
    private $config;

    /** @var string */
    private $historyPath;

    /** @var bool */
    private $maintenance;

    /** @var array */
    private $versions = [];

    /** @var array */
    private $histories = [];

	public function __construct(SessionInterface $session, ContainerInterface $container)
	{
        $this->session = $session;
		$this->container = $container;
        $this->config = $container->getParameter(EghojansuSetupBundle::BUNDLE_ID);
        foreach ($this->config['versions'] ?: [] as $key => $version) {
            $this->versions[$version['version']] = $version + $this->versionStatus($version['version']);
        }
	}

    /**
     * Get maintenance status
     * @return boolean
     */
    public function isMaintenance()
    {
        if (is_null($this->maintenance)) {
            $filePath = $this->getFile(self::MAINTENANCE_FILENAME);

            $this->maintenance = file_exists($filePath) ?
                $this->getYamlContent($filePath, self::MAINTENANCE_MAINTENANCE_KEY) : false;
        }

        return $this->maintenance;
    }

    public function setMaintenance($maintenance, Request $request = null)
    {
        $filePath = $this->getFile(self::MAINTENANCE_FILENAME);
        $content = [];
        if (file_exists($filePath)) {
            $content = $this->getYamlContent($filePath);
        }
        $content[self::MAINTENANCE_MAINTENANCE_KEY] = $maintenance;

        if (!isset($content[self::MAINTENANCE_MAINTENANCE_LOG])) {
            $content[self::MAINTENANCE_MAINTENANCE_LOG] = [];
        }
        $content[self::MAINTENANCE_MAINTENANCE_LOG][] = [
            'maintenance' => $maintenance,
            'date' => new DateTime(),
            'ip' => $request ? $request->getClientIp() : null,
            'agent' => $request ? $request->headers->get('User-Agent') : null,
        ];
        $this->setYamlContent($filePath, $content, null, self::WATERMARK);

        $this->maintenance = $maintenance;
    }

    /**
     * Set authenticated status
     * @param boolean $authenticated
     */
    public function setAuthenticated($authenticated)
    {
    	$this->session->set(self::AUTHENTICATED_SESSION_NAME, $authenticated);
    }

    /**
     * check authenticated status
     * @return boolean
     */
    public function isAuthenticated()
    {
    	return $this->session->get(self::AUTHENTICATED_SESSION_NAME, false);
    }

    /**
     * Get parameter from container
     *
     * @param  string $name
     * @param  mixed $default
     * @return mixed
     */
    public function getParameter($name, $default = null)
    {
        $val = $this->container->hasParameter($name) ?
            $this->container->getParameter($name) : $default;

        return is_null($val) ? '~' : $val;
    }

    /**
     * Get config from setup
     *
     * @param  string $name
     * @param  mixed $default
     * @return mixed
     */
    public function getConfig($key, $default = null)
    {
        return array_key_exists($key, $this->config) ? $this->config[$key] : $default;
    }

    /**
     * Get version list
     * @return array
     */
    public function getVersions()
    {
        return $this->versions;
    }

    /**
     * Get info for version
     * @param  string $version
     * @return array
     */
    public function getVersion($version)
    {
        return $this->isVersionExists($version) ? $this->versions[$version] : [];
    }

    /**
     * Check if version exists
     * @param  string  $version
     * @return boolean
     */
    public function isVersionExists($version)
    {
        return array_key_exists($version, $this->versions);
    }

    /**
     * Check if a version was installed
     *
     * @param  string  $version
     * @return boolean
     */
    public function isVersionInstalled($version)
    {
        return $this->isVersionExists($version) && $this->versions[$version]['installed'];
    }

    /**
     * Check if previous version was installed
     *
     * @param  string  $version
     * @return boolean
     */
    public function isPreviousVersionInstalled($version)
    {
        if (!$this->isVersionExists($version)) {
            return false;
        }

        $counter = 1;
        $clone = $this->versions;
        reset($clone);
        while ($current = current($clone)) {
            $key = key($clone);

            if ($key === $version && $counter === 1) {
                return true;
            }

            $next = next($clone);
            $nextVersion = key($clone);

            if ($nextVersion === $version &&
                version_compare($key, $version, 'lt') &&
                $current['installed']) {
                return true;
            }
            $counter++;
        }

        return false;
    }

    /**
     * Read yaml
     * @param  string $file
     * @param  string $key
     * @return mixed
     */
    public function getYamlContent($file, $key = null)
    {
        if (!file_exists($file)) {
            throw new RuntimeException(sprintf('Parameters file "%s" was not exists', $file));
        }

        $content = Yaml::parse(file_get_contents($file));
        if (empty($content)) {
            return [];
        }

        if ($key) {
            if (!array_key_exists($key, $content)) {
                throw new RuntimeException(sprintf('No "%s" key in file "%s"', $key, $file));
            }

            return $content[$key];
        }

        return $content ?: [];
    }

    /**
     * Set yaml content
     * @param string $file
     * @param array  $content
     * @param string $key
     * @param string $prepend
     */
    public function setYamlContent($file, array $content, $key = null, $prepend = null)
    {
        $savedContent = $key ? [$key => $content] : $content;
        $yamlContent = $prepend . PHP_EOL . Yaml::dump($savedContent);

        $saved = @file_put_contents($file, $yamlContent);

        if (!$saved) {
            throw new RuntimeException(sprintf('Cannot save configuration to file "%s"', $file));
        }
    }

    /**
     * Update parameters file
     * @param  string  $version
     * @param  array   $data
     */
    public function updateParameters($version, array $data)
    {
        $vConfig = $this->getVersion($version);

        $data = ArrayHelper::create($data)->flatten()->getValue();
        if ($data) {
            $this->setYamlContent($vConfig['parameters']['destination'],
                $this->castData($data),
                $vConfig['parameters']['key'],
                self::WATERMARK
            );
        }
    }

    /**
     * Record history
     * @param  string  $version
     * @param  Request $request
     */
    public function recordSetupHistory($version, Request $request = null)
    {
        $vConfig = $this->getVersion($version);
        $filePath = $this->getFile(self::HISTORY_FILENAME);

        $savedContent = [];
        if (file_exists($filePath)) {
            $savedContent = $this->getYamlContent($filePath, self::HISTORY_INSTALLED_KEY);
        }

        $savedContent[] = [
            'version' => $version,
            'date' => new DateTime(),
            'ip' => $request ? $request->getClientIp() : null,
            'agent' => $request ? $request->headers->get('User-Agent') : null,
        ];

        $this->setYamlContent($filePath, $savedContent, self::HISTORY_INSTALLED_KEY);
    }

    /**
     * Get file from history dir
     * @param  string $file
     * @return string
     */
    public function getFile($file)
    {
        $historyPath = rtrim(strtr($this->config['history_path'], '\\', '/'), '/') . '/';
        if ($historyPath && !is_dir($historyPath)) {
            @mkdir($historyPath, 0777, true);
        }

        return $historyPath . $file;
    }

    /**
     * Cast data to php value
     * @param  array  $data
     * @return array
     */
    private function castData(array $data)
    {
        foreach ($data as $key => $value) {
            switch (strtolower($value)) {
                case 'false':
                case 'off':
                case 'no':
                    $value = false;
                    break;
                case 'true':
                case 'on':
                case 'yes':
                    $value = true;
                    break;
                case 'null':
                case '~':
                    $value = null;
                    break;
            }
            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * Get version status
     * @param  string $version
     * @return array
     */
    private function versionStatus($version)
    {
        if (empty($this->histories)) {
            $filePath = $this->getFile(self::HISTORY_FILENAME);

            if (file_exists($filePath)) {
                $this->histories = $this->getYamlContent($filePath, self::HISTORY_INSTALLED_KEY) ?: [];
            }
        }

        foreach ($this->histories as $history) {
            if (strcmp($version, $history['version']) === 0) {
                return [
                    'installed' => true,
                    'install_date' => $history['date'],
                ];
            }
        }

        return [
            'installed' => false,
            'install_date' => null,
        ];
    }
}
