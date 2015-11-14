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

namespace iMSCP\ApsStandard\Controller;

use iMSCP\ApsStandard\Service\ApsInstanceService;
use iMSCP\ApsStandard\Service\ApsInstanceSettingService;
use iMSCP\ApsStandard\Service\ApsPackageService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Class ApsInstanceController
 * @package iMSCP\ApsStandard\Controller
 */
class ApsInstanceController extends ApsAbstractController
{
	const INSTANCE_ENTITY_CLASS = 'iMSCP\\ApsStandard\\Entity\\ApsInstance';

	/**
	 * {@inheritdoc}
	 */
	public function handleRequest()
	{
		try {
			switch ($this->getRequest()->getMethod()) {
				case Request::METHOD_GET:
					$action = $this->getRequest()->query->get('action', 'index');

					if ($action == 'index') {
						$this->indexAtion();
					} elseif ($action == 'new') {
						$this->newAction();
					} else {
						$this->getResponse()->setStatusCode(405);
					}
					break;
				case Request::METHOD_POST:
					$this->createAction();
					break;
				case Request::METHOD_PUT:
					$this->updateAction();
					break;
				case Request::METHOD_DELETE:
					$this->deleteAction();
					break;
				default:
					$this->getResponse()->setStatusCode(405);
			}
		} catch (\Exception $e) {
			write_log(sprintf('Could not handle request: %s', $e->getMessage()), E_USER_ERROR);
			$this->fillResponseFromException($e);
		}

		$this->getResponse()->prepare($this->getRequest())->send();
	}

	/**
	 * List all application instances
	 *
	 * @return void
	 */
	protected function indexAtion()
	{
		$instances = $this->getInstanceService()->getInstances();
		$this->getResponse()->setContent($this->getSerializer()->serialize($instances, 'json'));
	}

	/**
	 * New application instance form
	 *
	 * @throws \Exception
	 * @return void
	 */
	protected function newAction()
	{
		$package = $this->getPackageService()->getPackage($this->getRequest()->query->getInt('id'));
		$instanceSettings = $this->getInstanceSettingService()->getSettingsFromMetadataFile($package);
		$this->getResponse()->setData(array('package_id' => $package->getId(), 'settings' => $instanceSettings));
	}

	/**
	 * Create a new application instance
	 *
	 * @return void
	 */
	protected function createAction()
	{
		$payload = @json_decode($this->getRequest()->getContent(), true);
		if (json_last_error() != JSON_ERROR_NONE || !isset($payload['package_id']) || !isset($payload['settings'])) {
			throw new \DomainException('Invalid payload.', 400);
		}

		$package = $this->getPackageService()->getPackage($payload['package_id']);
		$settings = $this->getInstanceSettingService()->getSettingObjectsFromArray($package, $payload['settings']);
		$errors = $this->getInstanceService()->createInstance($package, $settings);

		if (!count($errors)) {
			$this->getResponse()->setStatusCode(201);
			return;
		}

		$errMessages = array();

		foreach ($errors as $error) {
			if ($error instanceof ConstraintViolation) {
				$errMessages[] = $error->getMessage();
			} else {
				$errMessages[] = (string)$error;
			}
		}

		$this->getResponse()
			->setContent($this->getSerializer()->serialize(array('errors' => $errMessages), 'json'))
			->setStatusCode(400);
	}

	/**
	 * Update an application instance
	 *
	 * @throws \Exception
	 * @return void
	 */
	protected function updateAction()
	{
		$this->getInstanceService()->reinstallInstance($this->getRequest()->query->getInt('id'));
		$this->getResponse()->setStatusCode(204);
	}

	/**
	 * Delete an application instance
	 *
	 * @throws \Exception
	 * @return void
	 */
	protected function deleteAction()
	{
		$this->getInstanceService()->deleteInstance($this->getRequest()->query->getInt('id'));
		$this->getResponse()->setStatusCode(204);
	}

	/**
	 * Get package service
	 *
	 * @return ApsInstanceService
	 */
	protected function getInstanceService()
	{
		return $this->getServiceLocator()->get('ApsInstanceService');
	}

	/**
	 * Get setting form service
	 *
	 * @return ApsPackageService
	 */
	protected function getPackageService()
	{
		return $this->getServiceLocator()->get('ApsPackageService');
	}

	/**
	 * Get setting form service
	 *
	 * @return ApsInstanceSettingService
	 */
	protected function getInstanceSettingService()
	{
		return $this->getServiceLocator()->get('ApsInstanceSettingService');
	}
}
