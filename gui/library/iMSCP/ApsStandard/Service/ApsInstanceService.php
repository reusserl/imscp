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

namespace iMSCP\ApsStandard\Service;

use iMSCP\ApsStandard\Entity\ApsInstance;
use iMSCP\ApsStandard\Entity\ApsPackage;
use iMSCP\ApsStandard\Entity\ApsInstanceSetting;
use iMSCP\Entity\Admin;

/**
 * Class ApsInstanceService
 * @package iMSCP\ApsStandard\Service
 */
class ApsInstanceService extends ApsAbstractService
{
	/**
	 * Get all application instances
	 *
	 * @return ApsInstance[]
	 */
	public function getInstances()
	{
		$this->getEventManager()->dispatch('onGetApsInstances', array('context' => $this));
		$instance = $this->getEntityManager()->getRepository('Aps:Instance')->findBy(array(
			'owner' => $this->getAuth()->getIdentity()->admin_id
		));
		return $instance;
	}

	/**
	 * Create a new application instance
	 *
	 * @param ApsPackage $package Package which belongs to the newly created application instance
	 * @param ApsInstanceSetting[] $settings APS instance settings
	 * @return void
	 */
	public function createInstance($package, array $settings)
	{
		$this->getEventManager()->dispatch('onCreateApsInstance', array(
			'package' => $package, 'settings' => $settings, 'context' => $this
		));

		$instance = new ApsInstance();
		$instance
			->setPackage($package)
			->setOwner($this->getAdminEntity($this->getAuth()->getIdentity()->admin_id))
			->setSettings($settings)
			->setStatus('toadd');

		$this->validateInstance($instance);
		$entityManager = $this->getEntityManager();
		$entityManager->persist($instance);
		$entityManager->flush($instance);
		//send_request();
	}

	/**
	 * Reinstall the given application instance
	 *
	 * @throws \Exception
	 * @param int $id Instance identifier
	 * @return void
	 */
	public function reinstallInstance($id)
	{
		$this->getEventManager()->dispatch('onReinstallApsInstance', array('id' => $id, 'context' => $this));
		$entityManager = $this->getEntityManager();

		/** @var ApsInstance $instance */
		$instance = $entityManager->getRepository('Aps:Package')->findOneBy(array(
			'id' => $id, 'owner' => $this->getAuth()->getIdentity()->admin_id
		));

		if (!$instance) {
			throw new \Exception(tr('Application instance not found.'), 404);
		}

		$instance->setStatus('tochange');
		$entityManager->flush($instance);
		send_request();
	}

	/**
	 * Delete the given application instance
	 *
	 * @throws \Exception
	 * @param int $id Instance identifier
	 * @return void
	 */
	public function deleteInstance($id)
	{
		$this->getEventManager()->dispatch('onDeleteApsInstance', array('id' => $id, 'context' => $this));
		$entityManager = $this->getEntityManager();

		/** @var ApsInstance $instance */
		$instance = $entityManager->getRepository('Aps:Instance')->findOneBy(array(
			'id' => $id, 'owner' => $this->getAuth()->getIdentity()->admin_id
		));

		if (!$instance) {
			throw new \Exception(tr('Application instance not found.'), 404);
		}

		$instance->setStatus('todelete');
		$entityManager->flush($instance);
		send_request();
	}

	/**
	 * Get package service
	 *
	 * @return ApsPackageService
	 */
	protected function getPackageService()
	{
		return $this->getServiceLocator()->get('PackageService');
	}

	/**
	 * Get admin entity
	 *
	 * @param int $id Admin identifier
	 * @return Admin
	 */
	protected function getAdminEntity($id)
	{
		return $this->getEntityManager()->getRepository('Core:Admin')->find($id);
	}

	/**
	 * Validate the given application instance
	 *
	 * @throws \DomainException
	 * @param ApsInstance $instance
	 * @return void
	 */
	protected function validateInstance(ApsInstance $instance)
	{
		if (count($this->getValidator()->validate($instance)) > 0) {
			throw new \DomainException(tr('Invalid application instance.'), 400);
		}
	}
}
