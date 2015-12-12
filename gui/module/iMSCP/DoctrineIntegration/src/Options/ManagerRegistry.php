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

namespace iMSCP\DoctrineIntegration\Options;

use Zend\Stdlib\AbstractOptions;

/**
 * Class ManagerRegistry
 * @package iMSCP\DoctrineIntegration\Options
 */
class ManagerRegistry extends AbstractOptions
{
    /**
     * @var string Manager registry name
     */
    protected $name = 'ORM';

    /**
     * @var string The class name of the Driver
     */
    protected $defaultConnection = 'default';

    /**
     * @var string default manager
     */
    protected $defaultManager = 'default';

    /**
     * @var string Proxy interface name
     */
    protected $proxyInterfaceName = 'Doctrine\ORM\Proxy\Proxy';

    /**
     * Get manager registry name
     *
     * @return string
     */
    public function getName()
    {
        return $this->defaultConnection;
    }

    /**
     * Set manager registry name
     *
     * @param string $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = (string)$name;
        return $this;
    }

    /**
     * Get default connection
     *
     * @return string
     */
    public function getDefaultConnection()
    {
        return $this->defaultConnection;
    }

    /**
     * Set default connection
     *
     * @param string $defaultConnection
     * @return self
     */
    public function setDefaultConnection($defaultConnection)
    {
        $this->defaultConnection = (string)$defaultConnection;
        return $this;
    }

    /**
     * Get default manager
     *
     * @return string
     */
    public function getDefaultManager()
    {
        return $this->defaultManager;
    }

    /**
     * Set default manager
     *
     * @param string $defaultManager
     * @return self
     */
    public function setDefaultManager($defaultManager)
    {
        $this->defaultManager = (string)$defaultManager;
        return $this;
    }

    /**
     * Get proxy interface name
     *
     * @return string
     */
    public function getProxyInterfaceName()
    {
        return $this->proxyInterfaceName;
    }

    /**
     * Set proxy interface name
     *
     * @param string $proxyInterfaceName
     * @return self
     */
    public function setProxyInterfaceName($proxyInterfaceName)
    {
        $this->proxyInterfaceName = (string)$proxyInterfaceName;
        return $this;
    }
}
