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

namespace iMSCP\Core\Service;

use iMSCP\Core\Console\ServiceManagerHelper;
use iMSCP\Core\Events;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;

/**
 * Class CliFactory
 * @package iMSCP\Core\Service
 */
class CliFactory implements FactoryInterface
{
    /**
     * @var \Zend\EventManager\EventManagerInterface
     */
    protected $events;

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');

        /** @var ServiceManager $serviceLocator */
        $helperSet = new HelperSet(['sm' => new ServiceManagerHelper($serviceLocator)]);

        $cli = new Application;
        $cli->setName('i-MSCP FrontEnd Command Line Interface');
        $cli->setVersion($config['version']);
        $cli->setHelperSet($helperSet);
        $cli->setCatchExceptions(true);
        $cli->setAutoExit(false);

        // Load commands using event
        $this->getEventManager($serviceLocator)->trigger(Events::onAfterLoadCli, $cli, [
            'ServiceManager' => $serviceLocator
        ]);

        return $cli;
    }

    /**
     * Get event manager
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Zend\EventManager\EventManagerInterface
     */
    public function getEventManager(ServiceLocatorInterface $serviceLocator)
    {
        if (null === $this->events) {
            /* @var $events \Zend\EventManager\EventManagerInterface */
            $events = $serviceLocator->get('EventManager');
            $events->addIdentifiers([
                __CLASS__,
                'imscp'
            ]);
            $this->events = $events;
        }

        return $this->events;
    }
}
