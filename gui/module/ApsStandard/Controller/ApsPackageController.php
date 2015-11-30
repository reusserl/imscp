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

use iMSCP\ApsStandard\Service\ApsPackageService;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ApsPackageController
 * @package iMSCP\ApsStandard\Controller
 */
class ApsPackageController extends ApsAbstractController
{
	const PACKAGE_ENTITY_CLASS = 'iMSCP\\ApsStandard\\Entity\\ApsPackage';

	/**
	 * @var ApsPackageService
	 */
	protected $packageService;

	/**
	 * {@inheritdoc}
	 */
	public function handleRequest()
	{
		try {
			$request = $this->getRequest();
			$action = $request->query->get('action', 'default');

			switch ($request->getMethod()) {
				case Request::METHOD_GET:
					if($action == 'default') {
						if(!$request->query->has('id')) {
							$this->indexAction();
						} else {
							$this->readAction();
						}
					} elseif($action == 'getCategories') {
						$this->categoriesAction();
					} else {
						$this->getResponse()->setStatusCode(405);
					}
					break;
				case Request::METHOD_POST:

					$this->updateAction();
					$this->updateIndexAction();
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
	 * List packages
	 *
	 * @throws \Exception
	 * @return void
	 */
	protected function indexAction()
	{
		if (!in_array($this->getAuth()->getIdentity()->admin_type, array('admin', 'user'))) {
			throw new \Exception(tr('Action not allowed.'), 403);
		}

		$page = $this->getRequest()->query->getInt('page', 0);
		$limit = $this->getRequest()->query->getInt('count', 5);
		$offset = ($page) ? ($page - 1) * $limit : 0;
		$criterias = array_map('urldecode', $this->getRequest()->query->get('filter', []));
		$packages = $this->getPackageService()->getPackages($offset, $limit, $criterias);
		$this->getResponse()->setContent($this->getSerializer()->serialize($packages, 'json'));
	}

	/**
	 * Read package
	 *
	 * @throws \Exception
	 * @return void
	 */
	protected function readAction()
	{
		$adminType = $this->getAuth()->getIdentity()->admin_type;

		if (!in_array($adminType, array('admin', 'user'))) {
			throw new \Exception(tr('Action not allowed.'), 403);
		}

		$package = $this->getPackageService()->getPackage(
			$this->getRequest()->query->getInt('id'),
			['status' => $adminType == 'admin ?' ? ['locked', 'unlocked'] : 'unlocked']
		);

		$this->getResponse()->setContent($this->getSerializer()->serialize($package, 'json'));
	}

	/**
	 * Update package
	 *
	 * @throws \Exception
	 * @return void
	 */
	protected function updateAction()
	{
		if ($this->getAuth()->getIdentity()->admin_type !== 'admin') {
			throw new \Exception(tr('Action not allowed.'), 403);
		}

		$serializer = $this->getSerializer();
		$package = $serializer->deserialize($this->getRequest()->getContent(), self::PACKAGE_ENTITY_CLASS, 'json');
		$this->getPackageService()->updatePackage($package);
		$this->getResponse()->setContent($serializer->serialize($package, 'json'));
	}

	/**
	 * Update package index
	 *
	 * @throws \Exception
	 * @return void
	 */
	protected function updateIndexAction()
	{
		if ($this->getAuth()->getIdentity()->admin_type !== 'admin') {
			throw new \Exception(tr('Action not allowed.'), 403);
		}

		$this->getPackageService()->updatePackageIndex();
	}

	/**
	 * Get package categories
	 *
	 * @throws \Exception
	 */
	public function categoriesAction()
	{
		if (!in_array($this->getAuth()->getIdentity()->admin_type, array('admin', 'user'))) {
			throw new \Exception(tr('Action not allowed.'), 403);
		}

		$this->getResponse()->setData($this->getPackageService()->getPackageCategories());
	}

	/**
	 * Get package service
	 *
	 * @return ApsPackageService
	 */
	protected function getPackageService()
	{
		return $this->getServiceLocator()->get('ApsPackageService');
	}
}
