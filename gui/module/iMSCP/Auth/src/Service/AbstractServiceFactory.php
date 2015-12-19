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

use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class AbstractServiceFactory
 * @package iMSCP\Auth\Service
 */
class AbstractServiceFactory implements AbstractFactoryInterface
{
    /**
     * @var array|bool Last mapping result
     */
    protected $lastMappingResult;

    /**
     * {@inheritdoc}
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        if (!($mappingResult = $this->getFactoryMapping($serviceLocator, $requestedName))) {
            return false;
        }

        $this->lastMappingResult = $mappingResult;
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $lastMappingResult = $this->lastMappingResult;
        unset($this->lastMappingResult);

        if (!$lastMappingResult) {
            throw new ServiceNotFoundException();
        }

        /** @var AbstractFactory $factory */
        $factory = new $lastMappingResult['factoryClass'](
            $lastMappingResult['serviceName'], $lastMappingResult['componentType']
        );

        return $factory->createService($serviceLocator);
    }

    /**
     * Get mapping data for the given service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param string $name Service name
     * @return null|array
     */
    private function getFactoryMapping(ServiceLocatorInterface $serviceLocator, $name)
    {
        $matches = [];

        if (!preg_match(
            '/^
                imscp_auth
                \.
                (?P<componentType>authentication|authorization)
                \.
                (?P<serviceType>[a-z0-9_]+)
                \.
                (?P<serviceName>[a-z0-9_]+)
             $/x',
            $name,
            $matches
        )
        ) {
            return null;
        }

        $config = $serviceLocator->get('Config');
        $componentType = $matches['componentType'];
        $serviceType = $matches['serviceType'];
        $serviceName = $matches['serviceName'];

        if (!isset($config['imscp_auth']['service_factories'][$componentType])
            || !isset($config['imscp_auth']['service_factories'][$componentType][$serviceType])
            || !isset($config['imscp_auth'][$componentType][$serviceType][$serviceName])
        ) {
            return null;
        }

        return array(
            'componentType' => $componentType,
            'serviceType' => $serviceType,
            'serviceName' => $serviceName,
            'factoryClass' => $config['imscp_auth']['service_factories'][$componentType][$serviceType],
        );

    }
}
