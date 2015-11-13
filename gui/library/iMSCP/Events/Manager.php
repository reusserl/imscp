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
 * Class iMSCP_Events_Manager
 */
class iMSCP_Events_Manager implements iMSCP_Events_Manager_Interface
{
	/**
	 * @var iMSCP_Events_Listener_PriorityQueue[] Array that contains events listeners stacks.
	 */
	protected $events = array();

	/**
	 * Return iMSCP_Events_Aggregator instance
	 *
	 * @return iMSCP_Events_Aggregator
	 * @deprecated 1.1.6 (will be removed in later version)
	 */
	public static function getInstance()
	{
		return iMSCP_Events_Aggregator::getInstance();
	}

	/**
	 * {@inheritdoc}
	 */
	public function dispatch($event, $arguments = array())
	{
		$responses = new iMSCP_Events_Listener_ResponseCollection();

		if ($event instanceof iMSCP_Events_Description) {
			$eventObject = $event;
			$event = $eventObject->getName();
		} else {
			$eventObject = new iMSCP_Events_Event($event, $arguments);
		}

		$listeners = $this->getListeners($event);

		/** @var $listener iMSCP_Events_Listener */
		foreach ($listeners as $listener) {
			$responses->push(call_user_func($listener->getHandler(), $eventObject));

			if ($eventObject->propagationIsStopped()) {
				$responses->setStopped(true);
				break;
			}
		}

		return $responses;
	}

	/**
	 * {@inheritdoc}
	 */
	public function registerListener($event, $listener, $priority = 1)
	{
		if (is_array($event)) {
			$listeners = array();
			foreach ($event as $name) {
				$listeners[] = $this->registerListener($name, $listener, $priority);
			}

			return $listeners;
		}

		if (empty($this->events[$event])) {
			$this->events[$event] = new iMSCP_Events_Listener_PriorityQueue();
		}

		$listener = new iMSCP_Events_Listener($listener, array('event' => $event, 'priority' => $priority));
		$this->events[$event]->addListener($listener, $priority);
		return $listener;
	}

	/**
	 * {@inheritdoc}
	 */
	public function registerAggregate(iMSCP_Events_ListenerAggregateInterface $aggregate, $priority = 1)
	{
		$aggregate->register($this, $priority);
	}

	/**
	 * Unregister all listeners which listen on the given event
	 *
	 * @throws iMSCP_Events_Exception If $event is not a string
	 * @param  string $event The event for which any event must be removed.
	 * @return void
	 */
	public function unregisterListeners($event)
	{
		if (is_string($event)) {
			unset($this->events[$event]);
		} else {
			throw new iMSCP_Events_Exception(
				sprintf(__CLASS__ . '::' . __FUNCTION__ . '() expects a string, %s given.', gettype($event))
			);
		}
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
	public function unregisterListener(iMSCP_Events_Listener $listener)
	{
		$event = $listener->getMetadatum('event');

		if (!$event || empty($this->events[$event])) {
			return false;
		}

		if (!($this->events[$event]->removeListener($listener))) {
			return false;
		}

		if (!count($this->events[$event])) {
			unset($this->events[$event]);
		}

		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getEvents()
	{
		return array_keys($this->events);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getListeners($event)
	{
		if (!array_key_exists($event, $this->events)) {
			return new iMSCP_Events_Listener_PriorityQueue();
		}

		return $this->events[$event];
	}

	/**
	 * {@inheritdoc}
	 */
	public function clearListeners($event)
	{
		if (!empty($this->events[$event])) {
			unset($this->events[$event]);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function hasListener($eventName)
	{
		return (bool)count($this->getListeners($eventName));
	}
}
