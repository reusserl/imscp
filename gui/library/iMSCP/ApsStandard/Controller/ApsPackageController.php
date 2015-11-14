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
			switch ($this->getRequest()->getMethod()) {
				case Request::METHOD_GET:
					if ($this->getRequest()->query->has('id')) {
						$this->showAction();
					} else {
						$this->indexAction();
					}
					break;
				case Request::METHOD_PUT:
					$this->updateAction();
					break;
				case Request::METHOD_POST:
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
	 * @return void
	 */
	protected function indexAction()
	{
		$page = $this->getRequest()->query->getInt('page', 0);
		$limit = $this->getRequest()->query->getInt('count', 5);
		$offset = ($page === 0) ? 0 : ($page - 1) * $limit;
		$packages = $this->getPackageService()->getPageablePackageList($offset, $limit);
		$this->getResponse()->setContent($this->getSerializer()->serialize($packages, 'json'));
	}

	/**
	 * Show package details
	 *
	 * @return void
	 */
	protected function showAction()
	{
		$packageDetails = $this->getPackageService()->getPackageDetails($this->getRequest()->query->getInt('id'));
		$this->getResponse()->setContent($this->getSerializer()->serialize($packageDetails, 'json'));
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

		$packageService = $this->getPackageService();
		$package = $packageService->getPackageFromPayload($this->getRequest()->getContent());
		$packageService->updatePackageStatus($package->getId(), $package->getStatus());
		$this->getResponse()->setStatusCode(204);
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
	 * Get package service
	 *
	 * @return ApsPackageService
	 */
	protected function getPackageService()
	{
		return $this->getServiceLocator()->get('ApsPackageService');
	}
}
