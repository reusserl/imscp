<?php

namespace iMSCP\DoctrineIntegration\Options;

use Zend\Stdlib\AbstractOptions;

/**
 * Configuration options for Second Level Cache
 *
 * @license MIT
 * @link    http://www.doctrine-project.org/
 * @author  Michaël Gallego <mic.gallego@gmail.com>
 * @package iMSCP\DoctrineIntegration\Options
 */
class SecondLevelCacheConfiguration extends AbstractOptions
{
    /**
     * @var bool Enable the second level cache configuration
     */
    protected $enabled = false;

    /**
     * @var int Default lifetime
     */
    protected $defaultLifetime = 3600;

    /**
     * @var int Default lock lifetime
     */
    protected $defaultLockLifetime = 60;

    /**
     * @var string The file lock region directory (needed for some cache usage)
     */
    protected $fileLockRegionDirectory = '';

    /**
     * Configure the lifetime and lock lifetime per region. You must pass an associative array like this:
     *
     * [
     *     'My\Region' => ['lifetime' => 200, 'lock_lifetime' => 400]
     * ]
     *
     * @var array
     */
    protected $regions = [];

    /**
     * Is enabled?
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool)$enabled;
    }

    /**
     * Get default lifetime
     *
     * @return int
     */
    public function getDefaultLifetime()
    {
        return $this->defaultLifetime;
    }

    /**
     * Set default lifetime
     *
     * @param int $defaultLifetime
     */
    public function setDefaultLifetime($defaultLifetime)
    {
        $this->defaultLifetime = (int)$defaultLifetime;
    }

    /**
     * Get default lock lifetime
     *
     * @return int
     */
    public function getDefaultLockLifetime()
    {
        return $this->defaultLockLifetime;
    }

    /**
     * Set default lock lifetime
     *
     * @param int $defaultLockLifetime
     */
    public function setDefaultLockLifetime($defaultLockLifetime)
    {
        $this->defaultLockLifetime = (int)$defaultLockLifetime;
    }

    /**
     * Get file lock region directory
     *
     * @return string
     */
    public function getFileLockRegionDirectory()
    {
        return $this->fileLockRegionDirectory;
    }

    /**
     * Set file lock region directory
     *
     * @param string $fileLockRegionDirectory
     */
    public function setFileLockRegionDirectory($fileLockRegionDirectory)
    {
        $this->fileLockRegionDirectory = (string)$fileLockRegionDirectory;
    }

    /**
     * Get regions
     *
     * @return array
     */
    public function getRegions()
    {
        return $this->regions;
    }

    /**
     * Set regions
     *
     * @param array $regions
     */
    public function setRegions(array $regions)
    {
        $this->regions = $regions;
    }
}
