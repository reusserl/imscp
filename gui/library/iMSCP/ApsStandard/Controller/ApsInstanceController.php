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

use iMSCP\ApsStandard\Service\ApsInstanceService AS instanceService;
use iMSCP\ApsStandard\Service\ApsSettingFormService AS SettingFormService;

;
use iMSCP_Authentication as Auth;
use Symfony\Component\HttpFoundation\JsonResponse as Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ApsInstanceController
 * @package iMSCP\ApsStandard\Controller
 */
class ApsInstanceController extends ApsAbstractController
{
	const INSTANCE_ENTITY_CLASS = 'iMSCP\ApsStandard\Entity\ApsInstance';

	/**
	 * @var instanceService
	 */
	protected $instanceService;

	/**
	 * Constructor
	 *
	 * @param Request $request
	 * @param Response $response
	 * @param Auth $auth
	 * @param instanceService $instanceService
	 */
	public function __construct(Request $request, Response $response, Auth $auth, instanceService $instanceService)
	{
		parent::__construct($request, $response, $auth);
		$this->instanceService = $instanceService;
	}

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
						$this->getResponse()->setStatusCode(400);
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
			$this->createResponseFromException($e);
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
		$instances = $this->getSerializer()->serialize($this->getInstanceService()->getInstances(), 'json');
		$this->getResponse()->setContent($instances);
	}

	/**
	 * New application instance form
	 *
	 * @throws \Exception
	 * @return void
	 */
	protected function newAction()
	{
		$form = $this->getSettingFormService()->getForm($this->getRequest()->query->getInt('id'));
		$this->getResponse()->setData($form);
	}

	/**
	 * Create a new application instance
	 *
	 * @throws \Exception
	 * @throws \iMSCP_Exception
	 * @return void
	 */
	protected function createAction()
	{
		$this->getInstanceService()->createInstance(array());
		set_page_message(tr('Instance has been scheduled for creation.'), 'success');
		$this->getResponse()->setData(array('redirect' => 'instances.php'))->setStatusCode(201);
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
		$this->getResponse()->setData(array('message' => 'Instance has been scheduled for update.'));
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
		$this->getResponse()->setData(array('message' => 'Instance has been scheduled for deletion.'));
	}

	/**
	 * Get package service
	 *
	 * @return InstanceService
	 */
	protected function getInstanceService()
	{
		return $this->instanceService;
	}

	/**
	 * Get setting form service
	 *
	 * @return SettingFormService
	 */
	protected function getSettingFormService()
	{
		return $this->getServiceLocator()->get('ApsSettingFormService');
	}
}
