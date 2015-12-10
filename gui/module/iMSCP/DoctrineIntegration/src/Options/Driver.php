<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace iMSCP\DoctrineIntegration\Options;

use Zend\Stdlib\AbstractOptions;

/**
 * MappingDriver options
 *
 * @license MIT
 * @link http://www.doctrine-project.org/
 * @author Kyle Spraggs <theman@spiffyjr.me>
 * @package iMSCP\DoctrineIntegration\Options
 */
class Driver extends AbstractOptions
{
    /**
     * @var string The class name of the Driver
     */
    protected $class;

    /**
     * All drivers (except DriverChain) require paths to work on. You
     * may set this value as a string (for a single path) or an array
     * for multiple paths.
     *
     * @var array
     */
    protected $paths = [];

    /**
     * Set the cache key for the annotation cache. Cache key
     * is assembled as "doctrine_integration.cache.{key}" and pulled from
     * service locator. This option is only valid for the
     * AnnotationDriver.
     *
     * @var string
     */
    protected $cache = 'array';

    /**
     * Set the file extension to use. This option is only
     * valid for FileDrivers (XmlDriver, YamlDriver, PHPDriver, etc).
     *
     * @var string|null
     */
    protected $extension = null;

    /**
     * Set the driver keys to use which are assembled as
     * "doctrine_integration.driver.{key}" and pulled from the service
     * locator. This option is only valid for DriverChain.
     *
     * @var array
     */
    protected $drivers = [];

    /**
     * Get cache
     *
     * @return string
     */
    public function getCache()
    {
        return "doctrine_integration.cache.{$this->cache}";
    }

    /**
     * Set cache
     *
     * @param string $cache
     */
    public function setCache($cache)
    {
        $this->cache = $cache;
    }

    /**
     * Get class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Set class
     *
     * @param string $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * Get drivers
     *
     * @return array
     */
    public function getDrivers()
    {
        return $this->drivers;
    }

    /**
     * Set drivers
     *
     * @param array $drivers
     */
    public function setDrivers($drivers)
    {
        $this->drivers = $drivers;
    }

    /**
     * Get extensions
     *
     * @return string|null
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * Set extentions
     *
     * @param null $extension
     */
    public function setExtension($extension)
    {
        $this->extension = $extension;
    }

    /**
     * Get paths
     *
     * @return array
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * Set paths
     *
     * @param array $paths
     */
    public function setPaths($paths)
    {
        $this->paths = $paths;
    }
}
