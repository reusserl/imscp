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

namespace iMSCP\Dev;

use Symfony\Component\Console\Command\Command;
use Zend\Console\Console;
use Zend\EventManager\EventInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\DependencyIndicatorInterface;
use Zend\ModuleManager\ModuleManagerInterface;

/**
 * Class Module
 * @package iMSCP\Dev
 */
class Module implements ConfigProviderInterface, DependencyIndicatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(ModuleManagerInterface $manager)
    {
        if (Console::isConsole()) {
            $events = $manager->getEventManager();
            $events->getSharedManager()->attach('imscp.cli', 'onAfterLoadCli', [$this, 'initializeConsole']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getModuleDependencies()
    {
        return ['iMSCP\Core'];
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * Initializes the console with commands for i-MSCP
     *
     * @param \Zend\EventManager\EventInterface $event
     * @return void
     */
    public function initializeConsole(EventInterface $event)
    {
        /* @var $cli \Symfony\Component\Console\Application */
        $cli = $event->getTarget();

        /* @var $serviceLocator \Zend\ServiceManager\ServiceLocatorInterface */
        $serviceLocator = $event->getParam('ServiceManager');

        /** @var Command $command */
        $command = $serviceLocator->get('dev.development_mode');

        $cli->add($command);
    }
}
