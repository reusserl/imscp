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

namespace iMSCP\Auth\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class AbstractFactory
 * @package iMSCP\Auth\Factory
 */
abstract class AbstractFactory implements FactoryInterface
{
    /**
     * Would normally be set to authentication or authorization
     *
     * @var string Component type
     */
    protected $componentType;

    /**
     * @var string Service name
     */
    protected $name;

    /**
     * Constructor
     *
     * @param string $name
     * @param null $componentType
     */
    public function __construct($name, $componentType = null)
    {
        $this->name = $name;
        $this->componentType = $componentType;
    }

    /**
     * Gets options from configuration based on name
     *
     * @param  ServiceLocatorInterface $serviceLocator
     * @param  string $key
     * @param  null|string $name
     * @return \Zend\Stdlib\AbstractOptions
     * @throws \RuntimeException
     */
    public function getOptions(ServiceLocatorInterface $serviceLocator, $key, $name = null)
    {
        if ($name === null) {
            $name = $this->getName();
        }

        $options = $serviceLocator->get('Config');
        $options = $options['imscp_auth'];

        if ($componentType = $this->getComponentType()) {
            $options = $options[$componentType];
        }

        $options = isset($options[$key][$name]) ? $options[$key][$name] : null;

        if (null === $options) {
            $path = ($componentType) ? "$componentType.$key" : "$key";
            throw new \RuntimeException(
                sprintf('Options with name "%s" could not be found in "imscp_auth.%s"', $name, $path)
            );
        }

        $optionsClass = $this->getOptionsClass();

        return new $optionsClass($options);
    }

    /**
     * Get service name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get optional component type
     *
     * Would normally be set to authentication or authorization
     *
     * @return string
     */
    public function getComponentType()
    {
        return $this->componentType;
    }

    /**
     * Get the class name of the options associated with this factory
     *
     * @abstract
     * @return string
     */
    abstract protected function getOptionsClass();
}
