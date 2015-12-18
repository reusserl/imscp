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

namespace iMSCP\Core;

use iMSCP\Core\Config\DbConfigHandler;
use iMSCP\Core\Config\FileConfigHandler;
use Zend\Console\Console;
use Zend\EventManager\EventInterface;
use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\InitProviderInterface;
use Zend\ModuleManager\Listener\ConfigListener;
use Zend\ModuleManager\ModuleEvent;
use Zend\ModuleManager\ModuleManagerInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayUtils;
use Zend\Stdlib\CallbackHandler;

/**
 * Class Module
 * @package iMSCP\Core
 */
class Module implements InitProviderInterface, ConfigProviderInterface, BootstrapListenerInterface
{
    /**
     * @var CallbackHandler
     */
    protected $substituteMergeConfigEvent;

    /**
     * {@inheritdoc}
     */
    public function init(ModuleManagerInterface $manager)
    {
        $events = $manager->getEventManager();

        // Attach a listener which will delay the default configuration merging
        // process by stopping the event propagation as soon as possible (we let
        // the default listener do its job by attaching our listener with a smaller
        // priority).
        $this->substituteMergeConfigEvent = $events->attach(
            ModuleEvent::EVENT_MERGE_CONFIG, [$this, 'substituteMergeConfigEvent'], 999
        );

        // Attach a listener which is responsible to merge configuration from
        // database with the merged application configuration
        $events->attach(ModuleEvent::EVENT_MERGE_CONFIG, [$this, 'onMergeConfig'], -1000);

        if (Console::isConsole()) {
            // Attach the listener which is responsible to add console commands
            $events->getSharedManager()->attach('imscp.cli', Events::onAfterLoadCli, [$this, 'initializeConsole']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onBootstrap(EventInterface $appEvent)
    {
        if (Console::isConsole()) {
            return;
        }

        /** @var ApplicationEvent $appEvent */
        /** @var Application $application */
        $application = $appEvent->getApplication();

        // Initialize and start session
        /** @var \Zend\Session\ManagerInterface $sessionManager */
        $sessionManager = $application->getServiceManager()->get('SessionManager');
        $sessionManager->start();
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        // TODO: Should we wrap this in service factory?
        $moduleConfig = include __DIR__ . '/config/module.config.php';

        if (!($configFilePath = getenv('IMSCP_CONF'))) {
            switch (PHP_OS) {
                case 'FreeBSD':
                case 'OpenBSD':
                case 'NetBSD':
                    $configFilePath = '/usr/local/etc/imscp/imscp.conf';
                    break;
                default:
                    $configFilePath = '/etc/imscp/imscp.conf';
            }
        }

        $config = ArrayUtils::merge($moduleConfig, (new FileConfigHandler($configFilePath))->toArray());

        // Convert IDN to ASCII
        $config['DEFAULT_ADMIN_ADDRESS'] = encode_idna($config['DEFAULT_ADMIN_ADDRESS']);
        $config['SERVER_HOSTNAME'] = encode_idna($config['SERVER_HOSTNAME']);
        $config['BASE_SERVER_VHOST'] = encode_idna($config['BASE_SERVER_VHOST']);
        $config['DATABASE_HOST'] = encode_idna($config['DATABASE_HOST']);
        $config['DATABASE_USER_HOST'] = encode_idna($config['DATABASE_USER_HOST']);

        // Runtime configuration parameters
        $config['ROOT_TEMPLATE_PATH'] = realpath('themes/' . $config['USER_INITIAL_THEME']);
        if ($config['DEVMODE']) {
            $config['ASSETS_PATH'] = '/assets';
        } else {
            $config['ROOT_TEMPLATE_PATH'] .= '/dist';
            $config['ASSETS_PATH'] = '/dist/assets';
        }

        return $config;
    }

    /**
     * @listen ModuleEvent::EVENT_MERGE_CONFIG
     * @param ModuleEvent $e
     * @return void
     */
    public function onMergeConfig(ModuleEvent $e)
    {
        /** @var ServiceManager $serviceManager */
        $serviceManager = $e->getParam('ServiceManager');

        /** @var DbConfigHandler $dbConfig */
        $dbConfig = $serviceManager->get('DbConfig');

        $mergedConfig = $e->getConfigListener()->getMergedConfig(false);
        $mergedConfig = ArrayUtils::merge($mergedConfig, $dbConfig->toArray());
        $e->getConfigListener()->setMergedConfig($mergedConfig);
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService('Config', $mergedConfig);
        $serviceManager->setAllowOverride(false);
    }

    /**
     * @listen ModuleEvent::EVENT_MERGE_CONFIG
     * @param ModuleEvent $e
     * @return void
     */
    public function substituteMergeConfigEvent(ModuleEvent $e)
    {
        // We must detach yourself to avoid loop (think that we will trigger
        // the ModuleEvent::EVENT_MERGE_CONFIG by ourself later on
        $e->getTarget()->getEventManager()->detach($this->substituteMergeConfigEvent);

        // We attach our own listener on the ModuleEvent::EVENT_LOAD_MODULES_POST
        // which will sustitute the default merge logic
        $e->getTarget()->getEventManager()->attach(
            ModuleEvent::EVENT_LOAD_MODULES_POST, [$this, 'onLoadModulePost'], -9000
        );

        $e->stopPropagation();
    }

    /**
     * @listen ModuleEvent::EVENT_LOAD_MODULES_POST
     * @param ModuleEvent $e
     * @return void
     */
    public function onLoadModulePost(ModuleEvent $e)
    {
        // We trigger ModuleEvent::EVENT_MERGE_CONFIG again (some other components could listen on it)
        $e->getTarget()->getEventManager()->trigger(ModuleEvent::EVENT_MERGE_CONFIG, $e->getTarget(), $e);

        /** @var ConfigListener $configListener */
        $configListener = $e->getConfigListener();

        // If enabled, update the configuration cache
        if ($configListener->getOptions()->getConfigCacheEnabled()) {
            $configFile = $configListener->getOptions()->getConfigCacheFile();
            $content = "<?php\nreturn " . var_export($configListener->getMergedConfig(false), 1) . ';';
            file_put_contents($configFile, $content);
        }
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

        $commands = [
            'core.build_language_index',
            'core.update_database'
        ];

        $cli->addCommands(array_map([$serviceLocator, 'get'], $commands));
    }
}
