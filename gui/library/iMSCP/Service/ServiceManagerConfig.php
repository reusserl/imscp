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

namespace iMSCP\Service;

use iMSCP\Events\EventManagerAwareInterface;
use iMSCP_Events_Manager_Interface as EventManagerInterface;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;

/**
 * Class ServiceManagerConfig
 * @package iMSCP\Service
 */
class ServiceManagerConfig extends Config
{
	/**
	 * Services that can be instantiated without factories
	 *
	 * @var array
	 */
	protected $invokables = [];

	/**
	 * Service factories
	 *
	 * @var array
	 */
	protected $factories = [
		'EventManager' => 'iMSCP\Service\EventManagerFactory',
	];

	/**
	 * Abstract factories
	 *
	 * @var array
	 */
	protected $abstractFactories = [];

	/**
	 * Aliases
	 *
	 * @var array
	 */
	protected $aliases = [
		'iMSCP\Events\EventManagerInterface' => 'EventManager',
		'Zend\ServiceManager\ServiceLocatorInterface' => 'ServiceManager',
		'Zend\ServiceManager\ServiceManager' => 'ServiceManager',
		'ServiceLocator' => 'ServiceManager'
	];

	/**
	 * Shared services
	 *
	 * Services are shared by default; this is primarily to indicate services that should NOT be shared
	 *
	 * @var array
	 */
	protected $shared = [];

	/**
	 * Delegators
	 *
	 * @var array
	 */
	protected $delegators = [];

	/**
	 * Initializers
	 *
	 * @var array
	 */
	protected $initializers = [];

	/**
	 * Constructor
	 *
	 * Merges internal arrays with those passed via configuration
	 *
	 * @param  array $configuration
	 */
	public function __construct(array $configuration = [])
	{
		$this->initializers = [
			'EventManagerAwareInitializer' => function ($instance, ServiceLocatorInterface $serviceLocator) {
				if ($instance instanceof EventManagerAwareInterface) {
					/** @var EventManagerInterface $eventManager */
					$eventManager = $serviceLocator->get('EventManager');
					$instance->setEventManager($eventManager);
				}
			},
			'ServiceManagerAwareInitializer' => function ($instance, ServiceLocatorInterface $serviceLocator) {
				if ($serviceLocator instanceof ServiceManager && $instance instanceof ServiceManagerAwareInterface) {
					$instance->setServiceManager($serviceLocator);
				}
			},
			'ServiceLocatorAwareInitializer' => function ($instance, ServiceLocatorInterface $serviceLocator) {
				if ($instance instanceof ServiceLocatorAwareInterface) {
					$instance->setServiceLocator($serviceLocator);
				}
			},
		];

		$this->factories['ServiceManager'] = function (ServiceLocatorInterface $serviceLocator) {
			return $serviceLocator;
		};

		parent::__construct(array_merge_recursive(
			[
				'invokables' => $this->invokables,
				'factories' => $this->factories,
				'abstract_factories' => $this->abstractFactories,
				'aliases' => $this->aliases,
				'shared' => $this->shared,
				'delegators' => $this->delegators,
				'initializers' => $this->initializers
			],
			$configuration
		));
	}
}
