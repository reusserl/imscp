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
use iMSCP\ApsStandard\Command\UpdatePackageIndexCommand;
use iMSCP\Tools\Console\ConsoleEvent;
use iMSCP_Events_Event as Event;
use iMSCP_Events as Events;
use iMSCP_Events_Listener as Listener;
use iMSCP_Events_ListenerAggregateInterface as ListenerAggregateInterface;
use iMSCP_Events_Manager_Interface as EventManager;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class ApsStandardListener
 * @package iMSCP\ApsStandard\Listener
 */
class ApsStandardListener implements ListenerAggregateInterface, ServiceLocatorAwareInterface
{
	/**
	 * @var ServiceLocatorInterface
	 */
	protected $serviceLocator;

	/**
	 * @var Listener[]
	 */
	protected $listeners = [];

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
			[
				Events::onBeforeDeleteDomainAlias,
				Events::onBeforeDeleteSubdomain,
			],
			[$this, 'onDeleteDependentDomain'],
			$priority
		);

		$this->listeners[] = $eventManager->registerListener(
			Events::onBeforeDeleteCustomer, [$this, 'onDeleteDependentCustomer'], $priority
		);

		$this->listeners[] = $eventManager->registerListener(
			Events::onBeforeDeleteSqlDb, [$this, 'onDeleteDependentSqlDatabase'], 99
		);

		$this->listeners[] = $eventManager->registerListener(
			Events::onBeforeCreateConsoleApplication, [$this, 'onBeforeCreateConsoleHelperSet']
		);

		#$this->listeners[] = $eventManager->registerListener(
		#	Events::onBeforeDeleteSqlUser, [$this, 'onDeleteDependentSqlUser'], 99
		#);
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
	 * Deletes APS application instances which belong to the domain being deleted
	 *
	 * Transitional listener (will be removed when all domains will be stored in the same table)
	 *
	 * @param Event $event
	 * @return bool
	 */
	public function onDeleteDependentDomain(Event $event)
	{
		/** @var EntityManager $em */
		$em = $this->getServiceLocator()->get('EntityManager');
		$qb1 = $em->createQueryBuilder();
		$qb2 = $em->createQueryBuilder();
		$qb2
			->delete('Aps:ApsInstance', 'i')
			->where(
				$qb2->expr()->in('i.id', $qb1
					->select('IDENTITY(s.instance)')
					->from('Aps:ApsInstanceSetting', 's')
					->where($qb1->expr()->eq('s.name', $qb1->expr()->literal('__base_url_host__')))
					->andWhere($qb1->expr()->eq('s.value', '?0'))->getDQL()
				)
			);

		switch ($event->getName()) {
			case Events::onBeforeDeleteDomainAlias:
				$qb2->getQuery()->execute([$event->getParam('domainAliasName')]);
				break;
			case Events::onBeforeDeleteSubdomain:
				$qb2->getQuery()->execute([$event->getParam('subdomainName')]);
		}

		return true;
	}

	/**
	 * Deletes APS application instances which belong to the customer being deleted
	 *
	 * @param Event $event
	 * @return bool
	 */
	public function onDeleteDependentCustomer(Event $event)
	{
		/** @var EntityManager $em */
		$em = $this->getServiceLocator()->get('EntityManager');
		$qb = $em->createQueryBuilder();
		$qb
			->delete('Aps:ApsInstance', 'i')
			->where($qb->expr()->eq('i.owner', '?0'))
			->getQuery()->execute([$event->getParam('customerId')]);

		return true;
	}

	/**
	 * Prevent deletion of dependent SQL database
	 *
	 * @param Event $event
	 * @return bool TRUE on success, FALSE on failure
	 */
	public function onDeleteDependentSqlDatabase(Event $event)
	{
		/** @var EntityManager $em */
		$em = $this->getServiceLocator()->get('EntityManager');
		$result = $em->getRepository('Aps:ApsInstanceSetting')->findOneBy([
			'name' => '__db_name__',
			'value' => $event->getParam('sqlDbName')
		]);

		if ($result !== null) {
			set_page_message(tr('This SQL database is assigned to an application instance. You cannot delete it.'), 'error');
			$event->stopPropagation();
			return false;
		}

		return true;
	}

#	/**
#	 * Prevent deletion of dependent SQL user
#	 *
#	 * @param Event $event
#	 * @return bool TRUE on success, FALSE on failure
#	 */
#
#	public function onDeleteDependentSqlUser(Event $event)
#	{
#		/** @var EntityManager $em */
#		$em = $this->getServiceLocator()->get('EntityManager');
#		$result = $em->getRepository('Aps:ApsInstanceSetting')->findOneBy([
#			'name' => '__db_user__',
#			'value' => $event->getParam('sqlUserName')
#		]);
#
#		if ($result !== null) {
#			set_page_message(tr('This SQL user is assigned to an application instance. You cannot delete it.'), 'error');
#			$event->stopPropagation();
#			return false;
#		}
#
#		return true;
#	}

	/**
	 * Register APS standard commands in i-MSCP Frontend Command Line Tool
	 *
	 * @param ConsoleEvent $event
	 */
	public function onBeforeCreateConsoleHelperSet(ConsoleEvent $event)
	{
		$event->addCommand(new UpdatePackageIndexCommand());
	}
}
