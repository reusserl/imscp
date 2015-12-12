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

namespace iMSCP\DoctrineIntegration;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Zend\Console\Console;
use Zend\EventManager\EventInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\InitProviderInterface;
use Zend\ModuleManager\ModuleManagerInterface;

/**
 * Class Module
 * @package iMSCP\DoctrineIntegration
 */
class Module implements InitProviderInterface, ConfigProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(ModuleManagerInterface $manager)
    {
        // Registers an autoloading callable for annotations
        AnnotationRegistry::registerLoader(function ($className) {
            return class_exists($className);
        });

        if (Console::isConsole()) {
            $events = $manager->getEventManager();
            $events->getSharedManager()->attach('imscp.cli', 'onAfterLoadCli', [$this, 'initializeConsole']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * Initializes the console with additional commands from the ORM and DBAL
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

        $commands = [
            // DBAL commands
            'doctrine_integration.dbal_cmd.runsql',
            'doctrine_integration.dbal_cmd.import',

            // ORM Commands
            'doctrine_integration.clear_cache_metadata',
            'doctrine_integration.clear_cache_result',
            'doctrine_integration.clear_cache_query',
            'doctrine_integration.schema_tool_create',
            'doctrine_integration.schema_tool_update',
            'doctrine_integration.schema_tool_drop',
            'doctrine_integration.ensure_production_settings',
            'doctrine_integration.convert_d1_schema',
            'doctrine_integration.generate_repositories',
            'doctrine_integration.generate_entities',
            'doctrine_integration.generate_proxies',
            'doctrine_integration.convert_mapping',
            'doctrine_integration.run_dql',
            'doctrine_integration.validate_schema',
            'doctrine_integration.info'
        ];

        $cli->addCommands(array_map([$serviceLocator, 'get'], $commands));

        /** @var \iMSCP\DoctrineIntegration\Persistence\ManagerRegistry $managerRegistry */
        $managerRegistry = $serviceLocator->get('doctrine_integration.manager_registry.default');

        /* @var $entityManager \Doctrine\ORM\EntityManager */
        $entityManager = $managerRegistry->getManager();

        $helperSet = $cli->getHelperSet();
        $helperSet->set(new QuestionHelper(), 'dialog');
        $helperSet->set(new ConnectionHelper($entityManager->getConnection()), 'db');
        $helperSet->set(new EntityManagerHelper($entityManager), 'em');
    }
}
