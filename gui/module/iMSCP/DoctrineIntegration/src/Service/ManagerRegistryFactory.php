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

namespace iMSCP\DoctrineIntegration\Service;

use iMSCP\DoctrineIntegration\Persistence\ManagerRegistry;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class ManagerRegistryFactory
 * @package iMSCP\DoctrineIntegration\Service
 */
class ManagerRegistryFactory implements FactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config')['doctrine_integration'];

        $connections = [];
        if (isset($config['connection'])) {
            foreach (array_keys($config['connection']) as $name) {
                $connections[$name] = 'doctrine_integration.connection.' . $name;
            }
        }

        $managers = [];
        if (isset($config['entitymanager'])) {
            foreach (array_keys($config['entitymanager']) as $name) {
                $managers[$name] = 'doctrine_integration.entitymanager.' . $name;
            }
        }

        return new ManagerRegistry($serviceLocator, 'doctrine', $connections, $managers);
    }
}
