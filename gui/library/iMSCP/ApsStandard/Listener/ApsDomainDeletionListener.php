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

namespace iMSCP\ApsStandard\Listener;

use Doctrine\ORM\EntityManager;
use iMSCP_Events_Event as Event;
use iMSCP_Events as Events;
use iMSCP_Events_Listener as Listener;
use iMSCP_Events_ListenerAggregateInterface as ListenerAggregateInterface;
use iMSCP_Events_Manager_Interface as EventManager;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class DomainDeletionListener
 *
 * Transitional listener (will be removed when all domains will be stored in the same table)
 *
 * @package iMSCP\ApsStandard
 */
class ApsDomainDeletionListener implements ListenerAggregateInterface, ServiceLocatorAwareInterface
{
	/**
	 * @var ServiceLocatorInterface
	 */
	protected $serviceLocator;

	/**
	 * @var Listener[]
	 */
	protected $listeners = array();

	/**
	 * {@inheritdoc}
	 */
	public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
	{
		$this->serviceLocator = $serviceLocator;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getServiceLocator()
	{
		return $this->serviceLocator;
	}

	/**
	 * {@inheritdoc}
	 */
	public function register(EventManager $eventManager, $priority = 1)
	{
		$this->listeners = $eventManager->registerListener(
			array(
				Events::onBeforeDeleteDomainAlias,
				Events::onBeforeDeleteSubdomain
			),
			$this,
			$priority
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function unregister(EventManager $eventManager)
	{
		foreach ($this->listeners as $index => $listener) {
			$eventManager->unregisterListener($listener);
			unset($this->listeners[$index]);
		}
	}

	/**
	 * Deletes APS application instances which belong to the deleted domain
	 *
	 * @param Event $event
	 * @return void
	 */
	public function __invoke(Event $event)
	{
		/** @var EntityManager $em */
		$em = $this->getServiceLocator()->get('EntityManager');
		$qb1 = $em->createQueryBuilder();
		$qb2 = $em->createQueryBuilder();
		$qb2->delete('Aps:ApsInstance', 'i')->where($qb2->expr()->in(
			'i.id',
			$qb1->select('IDENTITY(s.instance)')
				->from('Aps:ApsInstanceSetting', 's')
				->where($qb1->expr()->eq('s.name', $qb1->expr()->literal('__base_url_host__')))
				->andWhere($qb1->expr()->eq('s.value', '?0'))->getDQL()
		));

		switch ($event->getName()) {
			case Events::onBeforeDeleteDomainAlias:
				$qb2->getQuery()->execute(array($event->getParam('domainAliasName')));
				break;
			case Events::onBeforeDeleteSubdomain:
				$qb2->getQuery()->execute(array($event->getParam('subdomainName')));
		}
	}
}
