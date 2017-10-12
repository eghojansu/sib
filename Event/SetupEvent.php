<?php

namespace Eghojansu\Bundle\SetupBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Eghojansu\Bundle\SetupBundle\Service\Setup;

class SetupEvent extends Event
{
    const POST_CONFIG = 'eghojansu_setup.events.post_config';

    /** @var Eghojansu\Bundle\SetupBundle\Service\Setup */
    private $setup;

    /** @var string */
    private $version;

    /** @var string */
    private $message;

    public function __construct(Setup $setup)
    {
        $this->setup = $setup;
    }

    /**
     * Check if current version was equals
     * 
     * @param  string  $version
     * @return boolean         
     */
    public function isVersion($version)
    {
        return $this->version === $version;
    }

    /**
     * Check if a version was exists in definition
     * 
     * @param  string  $version
     * @return boolean         
     */
    public function isVersionExists($version)
    {
        return $this->setup->isVersionExists($version);
    }

    /**
     * Check if a version was installed
     * 
     * @param  string  $version 
     * @return boolean          
     */
    public function isVersionInstalled($version)
    {
        return $this->setup->isVersionInstalled($version);
    }

    /**
     * Check if previous version was installed
     * 
     * @param  string  $version
     * @return boolean         
     */
    public function isPreviousVersionInstalled($version)
    {
        return $this->setup->isPreviousVersionInstalled($version);
    }

    /**
     * Set current version
     * 
     * @param string
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * Get current version
     * 
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set message
     * 
     * @param string
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * Get message
     * 
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Get version info
     * 
     * @return array
     */
    public function getVersionInfo()
    {
        return $this->setup->getVersion($this->version);
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
        return $this->setup->getParameter($name, $default);
    }

    /**
     * Get config from setup
     * 
     * @param  string $name    
     * @param  mixed $default 
     * @return mixed          
     */
    public function getConfig($name, $default = null)
    {
        return $this->setup->getConfig($name, $default);
    }
}
