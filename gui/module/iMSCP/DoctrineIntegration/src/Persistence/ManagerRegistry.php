<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

namespace iMSCP\DoctrineIntegration\Persistence;

use Doctrine\Common\Persistence\ManagerRegistry as ManagerRegistryInterface;
use Doctrine\ORM\ORMException;
use Zend\ServiceManager\ServiceManager;

/**
 * Class ManagerRegistry
 * @package iMSCP\DoctrineIntegration\Persistence
 */
class ManagerRegistry implements ManagerRegistryInterface
{
    /**
     * @var string Registry manager name
     */
    protected $name;

    /**
     * @var string default manager
     */
    protected $defaultManager;

    /**
     * @var string Default connection
     */
    protected $defaultConnection;

    /**
     * @var array managers
     */
    protected $managers;

    /**
     * @var array Connections
     */
    protected $connections;

    /**
     * @var string Proxy interface name
     */
    protected $proxyInterfaceName;

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * Constructor
     *
     * @param ServiceManager $serviceManager
     * @param string $name Registry manager name
     * @param array $connections Connection names
     * @param array $managers Manager names
     * @param string $defaultConnection Default connection name
     * @param string $defaultManager Default manager name
     * @param string $proxyInterfaceName
     */
    public function __constructor(
        ServiceManager $serviceManager,
        $name,
        array $connections,
        array $managers,
        $defaultManager = 'default',
        $defaultConnection = 'default',
        $proxyInterfaceName = 'Doctrine\ORM\Proxy\Proxy'
    )
    {
        $this->serviceManager = $serviceManager;
        $this->name = (string)$name;
        $this->connections = $connections;
        $this->managers = $managers;
        $this->defaultConnection = (string)$defaultConnection;
        $this->defaultManager = (string)$defaultManager;
        $this->proxyInterfaceName = (string)$proxyInterfaceName;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultConnectionName()
    {
        return $this->defaultConnection;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection($name = null)
    {
        if (null === $name) {
            $name = $this->defaultConnection;
        }

        if (!isset($this->connections[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'Doctrine %s Connection named "%s" does not exist.', $this->name, $name
            ));
        }

        return $this->getService($this->connections[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getConnections()
    {
        $connections = [];
        foreach ($this->connections as $name => $id) {
            $connections[$name] = $this->getService($id);
        }

        return $connections;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionNames()
    {
        return $this->connections;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultManagerName()
    {
        return $this->defaultManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getManager($name = null)
    {
        if (null === $name) {
            $name = $this->defaultManager;
        }

        if (!isset($this->managers[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'Doctrine %s manager named "%s" does not exist.', $this->name, $name
            ));
        }

        return $this->getService($this->managers[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getManagers()
    {
        $managers = [];
        foreach ($this->managers as $name) {
            $managers[$name] = $this->getService($name);
        }

        return $managers;
    }

    /**
     * {@inheritdoc}
     */
    public function resetManager($name = null)
    {
        if (null === $name) {
            $name = $this->defaultManager;
        }

        if (!isset($this->managers[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'Doctrine %s manager named "%s" does not exist.', $this->name, $name
            ));
        }

        $this->resetService($this->managers[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAliasNamespace($alias)
    {
        foreach (array_keys($this->getManagers()) as $name) {
            try {
                return $this->getManager($name)->getConfiguration()->getEntityNamespace($alias);
            } catch (ORMException $ex) {
            }
        }

        throw ORMException::unknownEntityNamespace($alias);
    }

    /**
     * {@inheritdoc}
     */
    public function getManagerNames()
    {
        return $this->managers;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository($persistentObject, $persistentManagerName = null)
    {
        return $this->getManager($persistentManagerName)->getRepository($persistentObject);
    }

    /**
     * {@inheritdoc}
     */
    public function getManagerForClass($class)
    {
        if (strpos($class, ':') !== false) {
            list($namespaceAlias, $simpleClassName) = explode(':', $class, 2);
            $class = $this->getAliasNamespace($namespaceAlias) . '\\' . $simpleClassName;
        }

        $proxyClass = new \ReflectionClass($class);

        if ($proxyClass->implementsInterface($this->proxyInterfaceName)) {
            if (!$parentClass = $proxyClass->getParentClass()) {
                return null;
            }

            $class = $parentClass->getName();
        }

        foreach ($this->managers as $name) {
            $manager = $this->serviceManager->get($name);
            if (!$manager->getMetadataFactory()->isTransient($class)) {
                return $manager;
            }
        }

        return null;
    }

    /**
     * Set service manager
     *
     * @param ServiceManager $serviceManager
     */
    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }

    /**
     * Fetches/creates the given services
     *
     * A service in this context is a connection or a manager instance.
     *
     * @param string $name The name of the service
     * @return object The instance of the given service
     */
    protected function getService($name)
    {
        return $this->serviceManager->get($name);
    }

    /**
     * Resets the given services
     *
     * A service in this context is a a connection or a manager instance.
     *
     * @param string $name The name of the service
     * @return void
     */
    protected function resetService($name)
    {
        $this->serviceManager->setAllowOverride(true);
        $this->serviceManager->setService($name, null);
        $this->serviceManager->setAllowOverride(false);
    }
}
