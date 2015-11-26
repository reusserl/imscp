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

namespace iMSCP\Doctrine\Persistence;

use Doctrine\Common\Persistence\AbstractManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;

/**
 * Class ManagerRegistry
 * @package iMSCP\Doctrine\Persistence
 */
class ManagerRegistry extends AbstractManagerRegistry implements ServiceManagerAwareInterface
{
	/** @var ServiceManager */
	protected $serviceManager;

	/**
	 * {@inheritdoc}
	 */
	protected function getService($name)
	{
		return $this->serviceManager->get($name);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function resetService($name)
	{
		$this->serviceManager->setService($name, null);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getAliasNamespace($alias)
	{
		foreach (array_keys($this->getManagers()) as $name) {
			$manager = $this->getManager($name);

			if ($manager instanceof EntityManager) {
				try {
					return $manager->getConfiguration()->getEntityNamespace($alias);
				} catch (ORMException $ex) {
					// Probably mapped by another entity manager, or invalid, just ignore this here.
				}
			} else {
				throw new \LogicException(sprintf('Unsupported manager type "%s".', get_class($manager)));
			}
		}

		throw new \RuntimeException(sprintf('The namespace alias "%s" is not known to any manager.', $alias));
	}

	/**
	 * {@inheritdoc}
	 */
	public function setServiceManager(ServiceManager $serviceManager)
	{
		$this->serviceManager = $serviceManager;
	}
}
