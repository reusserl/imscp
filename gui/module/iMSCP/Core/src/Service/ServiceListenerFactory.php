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

use Zend\ModuleManager\Listener\ServiceListener;
use Zend\ModuleManager\Listener\ServiceListenerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\Exception\InvalidArgumentException;
use Zend\Stdlib\Exception\RuntimeException;

/**
 * Class ServiceListenerFactory
 * @package iMSCP\Core\Service
 */
class ServiceListenerFactory implements FactoryInterface
{
    /**
     * @var string
     */
    const MISSING_KEY_ERROR = 'Invalid service listener options detected, %s array must contain %s key.';

    /**
     * @var string
     */
    const VALUE_TYPE_ERROR = 'Invalid service listener options detected, %s must be a string, %s given.';

    /**
     * Default service configuration -- can be overridden by modules.
     *
     * @var array
     */
    protected $defaultServiceConfig = [
        'invokables' => [
        ],
        'factories' => [
            'Authentication' => 'iMSCP\Core\Service\AuthenticationFactory',
            'Application' => 'iMSCP\Core\Service\ApplicationFactory',
            'Config' => 'iMSCP\Core\Service\ConfigFactory',
            'DbConfig' => 'iMSCP\Core\Service\DbConfigFactory',
            'EncryptionDataService' => 'iMSCP\Core\Service\EncryptionDataService',
            'ManagerRegistry' => 'iMSCP\Core\Service\ManagerRegistryFactory',
            'navigation' => 'iMSCP\Core\Service\NavigationFactory',
            'Request' => 'iMSCP\Core\Service\RequestServiceFactory',
            'Response' => 'iMSCP\Core\Service\ResponseFactory',
            'Serializer' => 'iMSCP\Core\Service\SerializerFactory',
            'Translator' => 'iMSCP\Core\Service\TranslatorFactory',
            'Validator' => 'iMSCP\Core\Service\ValidatorFactory'
        ],
        'aliases' => [
            'Configuration' => 'Config'
        ],
        'abstract_factories' => []
    ];

    /**
     * Create the service listener service
     *
     * Tries to get a service named ServiceListenerInterface from the service locator, otherwise creates a
     * Zend\ModuleManager\Listener\ServiceListener service, passing it the service locator instance and the default
     * service configuration, which can be overridden by modules.
     *
     * It looks for the 'service_listener_options' key in the application config and tries to add service manager as
     * configured. The value of 'service_listener_options' must be a list (array) which contains the following keys:
     *   - service_manager: the name of the service manage to create as string
     *   - config_key: the name of the configuration key to search for as string
     *   - interface: the name of the interface that modules can implement as string
     *   - method: the name of the method that modules have to implement as string
     *
     * @param  ServiceLocatorInterface $serviceLocator
     * @return ServiceListener
     * @throws InvalidArgumentException For invalid configurations.
     * @throws RuntimeException
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $configuration = $serviceLocator->get('ApplicationConfig');

        if ($serviceLocator->has('ServiceListenerInterface')) {
            $serviceListener = $serviceLocator->get('ServiceListenerInterface');

            if (!$serviceListener instanceof ServiceListenerInterface) {
                throw new RuntimeException(
                    'The service named ServiceListenerInterface must implement ' .
                    'Zend\ModuleManager\Listener\ServiceListenerInterface'
                );
            }

            $serviceListener->setDefaultServiceConfig($this->defaultServiceConfig);
        } else {
            /** @var ServiceManager $serviceManager */
            $serviceManager = $serviceLocator;
            $serviceListener = new ServiceListener($serviceManager, $this->defaultServiceConfig);
        }

        if (isset($configuration['service_listener_options'])) {
            if (!is_array($configuration['service_listener_options'])) {
                throw new InvalidArgumentException(sprintf(
                    'The value of service_listener_options must be an array, %s given.',
                    gettype($configuration['service_listener_options'])
                ));
            }

            foreach ($configuration['service_listener_options'] as $key => $newServiceManager) {
                if (!isset($newServiceManager['service_manager'])) {
                    throw new InvalidArgumentException(sprintf(self::MISSING_KEY_ERROR, $key, 'service_manager'));
                } elseif (!is_string($newServiceManager['service_manager'])) {
                    throw new InvalidArgumentException(sprintf(
                        self::VALUE_TYPE_ERROR, 'service_manager', gettype($newServiceManager['service_manager'])
                    ));
                }

                if (!isset($newServiceManager['config_key'])) {
                    throw new InvalidArgumentException(sprintf(self::MISSING_KEY_ERROR, $key, 'config_key'));
                } elseif (!is_string($newServiceManager['config_key'])) {
                    throw new InvalidArgumentException(sprintf(
                        self::VALUE_TYPE_ERROR, 'config_key', gettype($newServiceManager['config_key'])
                    ));
                }

                if (!isset($newServiceManager['interface'])) {
                    throw new InvalidArgumentException(sprintf(self::MISSING_KEY_ERROR, $key, 'interface'));
                } elseif (!is_string($newServiceManager['interface'])) {
                    throw new InvalidArgumentException(sprintf(
                        self::VALUE_TYPE_ERROR, 'interface', gettype($newServiceManager['interface'])
                    ));
                }

                if (!isset($newServiceManager['method'])) {
                    throw new InvalidArgumentException(sprintf(self::MISSING_KEY_ERROR, $key, 'method'));
                } elseif (!is_string($newServiceManager['method'])) {
                    throw new InvalidArgumentException(sprintf(
                        self::VALUE_TYPE_ERROR, 'method', gettype($newServiceManager['method'])
                    ));
                }

                $serviceListener->addServiceManager(
                    $newServiceManager['service_manager'],
                    $newServiceManager['config_key'],
                    $newServiceManager['interface'],
                    $newServiceManager['method']
                );
            }
        }

        return $serviceListener;
    }
}
