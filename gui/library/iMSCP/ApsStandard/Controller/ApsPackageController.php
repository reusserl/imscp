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

use iMSCP\ApsStandard\Entity\ApsPackage;
use iMSCP\ApsStandard\Service\ApsPackageService;
use iMSCP_Authentication as Auth;
use Symfony\Component\HttpFoundation\JsonResponse as Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ApsPackageController
 * @package iMSCP\ApsStandard\Controller
 */
class ApsPackageController extends ApsAbstractController
{
	const PACKAGE_ENTITY_CLASS = 'iMSCP\ApsStandard\Entity\ApsPackage';

	/**
	 * @var ApsPackageService
	 */
	protected $apsPackageService;

	/**
	 * Constructor
	 *
	 * @param Request $request
	 * @param Response $response
	 * @param Auth $auth
	 * @param ApsPackageService $apsPackageService
	 */
	public function __construct(Request $request, Response $response, Auth $auth, ApsPackageService $apsPackageService)
	{
		parent::__construct($request, $response, $auth);
		$this->apsPackageService = $apsPackageService;
	}

	/**
	 * Get package service
	 *
	 * @return ApsPackageService
	 */
	public function getApsPackageService()
	{
		return $this->apsPackageService;
	}

	/**
	 * {@inheritdoc}
	 */
	public function handleRequest()
	{
		try {
			switch ($this->getRequest()->getMethod()) {
				case Request::METHOD_GET:
					if ($this->getRequest()->query->has('id')) {
						$this->showDetails($this->getRequest()->query->getInt('id'));
					} else {
						$this->index();
					}
					break;
				case Request::METHOD_PUT:
					$this->changeStatus();
					break;
				case Request::METHOD_POST:
					$this->updateIndex();
					break;
				default:
					$this->getResponse()->setStatusCode(405);
			}
		} catch (\Exception $e) {
			write_log(sprintf('Could not handle request: %s', $e->getMessage()), E_USER_ERROR);
			$this->createResponseFromException($e);
		}

		$this->getResponse()->prepare($this->request)->send();
	}

	/**
	 * Lists all packages
	 *
	 * @void
	 */
	protected function index()
	{
		$this->getResponse()->setContent(
			$this->getSerializer()->serialize($this->getApsPackageService()->getPackages(), 'json')
		);
	}

	/**
	 * Show package details
	 *
	 * @param int $id Package identity
	 * @return void
	 */
	protected function showDetails($id)
	{
		$this->getResponse()->setContent(
			$this->getSerializer()->serialize($this->getApsPackageService()->getPackageDetails($id), 'json')
		);
	}

	/**
	 * Change package status
	 *
	 * @throws \Exception
	 * @return void
	 */
	protected function changeStatus()
	{
		if ($this->getAuth()->getIdentity()->admin_type !== 'admin') {
			throw new \Exception(tr('Action not allowed.'), 403);
		}

		/** @var ApsPackage $package */
		$package = $this->getSerializer()->deserialize(
			$this->getRequest()->getContent(), self::PACKAGE_ENTITY_CLASS, 'json'
		);
		$this->getApsPackageService()->updatePackageStatus($package->getId(), $package->getStatus());
		$this->getResponse()->setStatusCode(204);
	}

	/**
	 * Update package index
	 *
	 * @throws \Exception
	 * @return void
	 */
	protected function updateIndex()
	{
		if ($this->getAuth()->getIdentity()->admin_type !== 'admin') {
			throw new \Exception(tr('Action not allowed.'), 403);
		}

		$this->getApsPackageService()->updatePackageIndex();
		$this->getResponse()->setData(array('message' => tr('Package index has been updated.')));
	}
}
