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

/**
 * Class iMSCP_Events_Aggregator
 */
class iMSCP_Events_Aggregator implements iMSCP_Events_Manager_Interface
{
	/**
	 * @var iMSCP_Events_Aggregator
	 */
	protected static $instance;

	/**
	 * @var array map event to event type
	 */
	protected $events = array();

	/**
	 * @var iMSCP_Events_Manager_Interface[]
	 */
	protected $eventManagers;

	/**
	 * Constructor
	 */
	protected function __construct()
	{
		// Event Manager used for events which are not explicitely declared
		$this->eventManagers['application'] = new iMSCP_Events_Manager();
	}

	/**
	 * Singleton object - Make clone unavailable
	 *
	 * @return iMSCP_Events_Aggregator
	 */
	public static function getInstance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get the given event manager
	 *
	 * @param string $name Event manager unique name
	 * @return iMSCP_Events_Manager_Interface|null
	 */
	public function getEventManager($name)
	{
		if (isset($this->eventManagers[$name])) {
			return $this->eventManagers[$name];
		}

		return null;
	}

	/**
	 * Reset instance
	 *
	 * @static
	 * @return void
	 */
	public static function resetInstance()
	{
		self::$instance = null;
	}

	/**
	 * Add events
	 * @param $type
	 * @param array $events
	 * @return iMSCP_Events_Aggregator
	 */
	public function addEvents($type, array $events = array())
	{
		if (isset($this->events[$type])) {
			$this->events[$type] = array_merge($this->events[$type], $events);
		} else {
			$this->events[$type] = $events;
			$this->eventManagers[$type] = new iMSCP_Events_Manager();
		}

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function dispatch($event, $arguments = array())
	{
		if (($eventType = $this->getEventType($event))) {
			return $this->eventManagers[$eventType]->dispatch($event, $arguments);
		}

		return $this->eventManagers['application']->dispatch($event, $arguments);
	}

	/**
	 * {@inheritdoc}
	 */
	public function registerListener($event, $listener, $priority = 1)
	{
		if (is_array($event)) {
			$listeners = array();
			foreach ($event as $e) {
				$listeners[] = $this->registerListener($e, $listener, $priority);
			}

			return $listeners;
		}

		if (($eventType = $this->getEventType($event))) {
			return $this->eventManagers[$eventType]->registerListener($event, $listener, $priority);
		}

		$this->addEvents('application', (array)$event);
		return $this->eventManagers['application']->registerListener($event, $listener, $priority);
	}

	/**
	 * {@inheritdoc}
	 */
	public function registerAggregate(iMSCP_Events_ListenerAggregateInterface $aggregate, $priority = 1)
	{
		$aggregate->register($this, $priority);
	}

	/**
	 * {@inheritdoc}
	 */
	public function unregisterListener(iMSCP_Events_Listener $listener)
	{
		$event = $listener->getMetadatum('event');

		if (($eventType = $this->getEventType($event))) {
			$this->eventManagers[$eventType]->unregisterListener($listener);
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function unregisterAggregate(iMSCP_Events_ListenerAggregateInterface $aggregate)
	{
		$aggregate->unregister($this);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getEvents($type = null)
	{
		$type = (string)$type;

		if (!$type) {
			$events = array();

			foreach ($this->events as $type) {
				$events = array_merge($events, $type);
			}

			return $events;
		}

		if (isset($this->events[$type])) {
			return $this->events[$type];
		}

		return array();
	}

	/**
	 * Get event type
	 *
	 * @param $event
	 * @return string|null
	 */
	public function getEventType($event)
	{
		foreach ($this->events as $eventType => $events) {
			if (in_array($event, $events)) {
				return $eventType;
			}
		}

		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getListeners($event)
	{
		if (($eventType = $this->getEventType($event))) {
			return $this->eventManagers[$eventType]->getListeners($event);
		}

		return new iMSCP_Events_Listener_PriorityQueue();
	}

	/**
	 * {@inheritdoc}
	 */
	public function clearListeners($event)
	{
		if (($eventType = $this->getEventType($event))) {
			$this->eventManagers[$eventType]->clearListeners($event);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function hasListener($event)
	{
		if (($eventType = $this->getEventType($event))) {
			return $this->eventManagers[$eventType]->hasListener($event);
		}

		return false;
	}
}
