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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Zend\EventManager\EventManagerInterface;
use Zend\ServiceManager\ServiceManager;

/**
 * Class Application
 * @package iMSCP
 */
class Application implements ApplicationInterface
{
	/** @var Application */
	static protected $instance;

	/**
	 * @var array
	 */
	protected $configuration = null;

	/**
	 * @var EventManagerInterface
	 */
	protected $events;

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * @var Response
	 */
	protected $response;

	/**
	 * @var ServiceManager
	 */
	protected $serviceManager;

	/**
	 * @var array Default listeners
	 */
	protected $defaultListeners = [];

	/**
	 * @var ApplicationEvent
	 */
	protected $event;

	/**
	 * Constructor
	 *
	 * @param mixed $configuration
	 * @param ServiceManager $serviceManager
	 */
	public function __construct($configuration, ServiceManager $serviceManager)
	{
		$this->configuration = $configuration;
		$this->serviceManager = $serviceManager;

		/** @var EventManagerInterface $eventManager */
		$eventManager = $serviceManager->get('EventManager');
		$this->setEvents($eventManager);

		$this->request = $serviceManager->get('Request');
		$this->response = $serviceManager->get('Response');
	}

	/**
	 * Retrieve the application configuration
	 *
	 * @return array|object
	 */
	public function getConfig()
	{
		return $this->serviceManager->get('Config');
	}

	/**
	 * Bootstrap the application
	 *
	 * Defines and binds the ApplicationEvent, and passes it the request and response.
	 * Triggers the bootstrap event.
	 *
	 * @param array $listeners List of listeners to attach.
	 * @return Application
	 */
	public function bootstrap(array $listeners = [])
	{
		$serviceManager = $this->serviceManager;
		$eventManager = $this->events;

		$listeners = array_unique(array_merge($this->defaultListeners, $listeners));

		foreach ($listeners as $listener) {
			$eventManager->attach($serviceManager->get($listener));
		}

		$this->event = $event = (new ApplicationEvent('application', $this))
			->setRequest($this->request)
			->setResponse($this->response);

		$eventManager->trigger(ApplicationEvent::EVENT_BOOTSTRAP, $event);

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getServiceManager()
	{
		return $this->serviceManager;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * Static method for quick and easy initialization of the Application
	 *
	 * If you use this init() method, you cannot specify a service with the name of 'ApplicationConfig' in your service
	 * manager config. This name is reserved to hold the array from application.config.php.
	 *
	 * The following services can only be overridden from application.config.php:
	 *
	 * - ModuleManager
	 * - SharedEventManager
	 * - EventManager & Zend\EventManager\EventManagerInterface
	 *
	 * All other services are configured after module loading, thus can be
	 * overridden by modules.
	 *
	 * @param array $configuration
	 * @return Application
	 */
	public static function init($configuration = [])
	{
		$smConfig = isset($configuration['service_manager']) ? $configuration['service_manager'] : [];
		$serviceManager = new ServiceManager(new Service\ServiceManagerConfig($smConfig));
		$serviceManager->setService('ApplicationConfig', $configuration);
		$serviceManager->get('ModuleManager')->loadModules();
		$listenersFromAppConfig = isset($configuration['listeners']) ? $configuration['listeners'] : [];
		$config = $serviceManager->get('Config');
		$listenersFromConfigService = isset($config['listeners']) ? $config['listeners'] : [];
		$listeners = array_unique(array_merge($listenersFromConfigService, $listenersFromAppConfig));
		return $serviceManager->get('Application')->bootstrap($listeners);
	}

	/**
	 * {@inheritdoc}
	 */
	public function run()
	{
		// Nothing to do ATM
	}

	/**
	 * Set the event manager instance
	 *
	 * @param  EventManagerInterface $events
	 * @return Application
	 */
	public function setEvents(EventManagerInterface $events)
	{
		$events->setIdentifiers([__CLASS__, get_class($this)]);
		$this->events = $events;
		return $this;
	}

	/**
	 * Get the application event instance
	 *
	 * @return ApplicationEvent
	 */
	public function getApplicationEvent()
	{
		return $this->event;
	}

	/**
	 * Retrieve the event manager
	 *
	 * Lazy-loads an EventManager instance if none registered.
	 *
	 * @return EventManagerInterface
	 */
	public function getEvents()
	{
		return $this->events;
	}

	/**
	 * Get application instance
	 *
	 * Transitional function allowing to deal with i-MSCP procedural code. Will be removed in v2.0.0
	 *
	 * @throws \Exception
	 */
	static public function getInstance()
	{
		if(null === self::$instance) {
			throw new \Exception('Application has not been initialized yet');
		}

		return self::$instance;
	}
}
